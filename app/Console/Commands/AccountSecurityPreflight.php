<?php

namespace App\Console\Commands;

use App\Services\MfaDeviceTrust;
use App\Services\SecurityMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountSecurityPreflight extends Command
{
    protected $signature = 'security:preflight {--database : Explicitly inspect the configured database using read-only queries}';

    protected $description = 'Report account-security configuration and optional database metadata without printing secrets';

    public function handle(): int
    {
        $this->table(['Check', 'Result'], [
            ['System mail configuration', SecurityMail::available() ? 'ready for controlled test' : 'not ready'],
            ['Application URL', str_starts_with((string) config('app.url'), 'https://') ? 'HTTPS' : 'HTTP (local development only)'],
            ['Session encryption', config('session.encrypt') ? 'enabled' : 'disabled: enable before rollout'],
            ['Debug', config('app.debug') ? 'enabled (local only)' : 'disabled'],
            ['Queue', config('queue.default')],
            ['Admin browser inactivity limit', app(MfaDeviceTrust::class)->inactivityDays().' days (30-day absolute maximum)'],
        ]);
        if (! $this->option('database')) {
            $this->line('Database not contacted. Use --database only after verifying this environment targets an authorized database.');

            return self::SUCCESS;
        }
        try {
            if (Schema::hasTable('users')) {
                $duplicates = DB::table('users')->selectRaw('LOWER(TRIM(email)) AS normalized')->groupByRaw('LOWER(TRIM(email))')->havingRaw('COUNT(*) > 1')->get()->count();
                $this->line('Normalized email collision groups: '.$duplicates);
            }
            foreach (['users', 'terms_acceptances', 'jobs', 'failed_jobs', 'cache', 'user_invitations', 'account_recoveries', 'security_events', 'mfa_trusted_devices', 'email_verification_codes'] as $table) {
                $this->line($table.': '.(Schema::hasTable($table) ? 'present' : 'missing'));
            }
            if (DB::connection()->getDriverName() === 'pgsql') {
                $role = DB::selectOne('SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname=current_user');
                $this->line('Runtime superuser: '.($role->rolsuper ? 'YES: unsuitable for least privilege' : 'no'));
                $this->line('Runtime BYPASSRLS: '.($role->rolbypassrls ? 'YES: RLS can be bypassed' : 'no'));
                $rows = DB::select("SELECT c.relname,c.relrowsecurity,c.relforcerowsecurity,pg_get_userbyid(c.relowner)=current_user AS owned FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE n.nspname=current_schema() AND c.relname IN ('users','user_invitations','invitation_institution','institution_user','security_events','account_recoveries','mfa_trusted_devices','email_verification_codes')");
                foreach ($rows as $row) {
                    $this->line($row->relname.': RLS='.($row->relrowsecurity ? 'on' : 'off').', FORCE='.($row->relforcerowsecurity ? 'on' : 'off').', runtime owner='.($row->owned ? 'yes' : 'no'));
                }
                $this->line('Policy count: '.DB::table('pg_policies')->where('schemaname', DB::raw('current_schema()'))->whereIn('tablename', ['users', 'user_invitations', 'invitation_institution', 'institution_user', 'security_events', 'account_recoveries', 'mfa_trusted_devices', 'email_verification_codes'])->count());
            }
        } catch (\Throwable $e) {
            $this->error('Database inspection failed. Verify availability and permissions. No database changes were made.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
