<?php

use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

// Render only Profile content with synthetic account data; no database or mail.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array']);
$session = app('session')->driver();
$session->start();
$request = Request::create('/profile');
$request->setLaravelSession($session);
$app->instance('request', $request);
$user = new User(['name' => 'Maria Santos', 'email' => 'maria@example.invalid', 'role' => 'admin']);
$user->is_active = true;
$user->setRelation('institutions', collect());
$source = file_get_contents(resource_path('views/profile/account.blade.php'));
$source = substr($source, strpos($source, "@section('content')") + strlen("@section('content')"));
$source = substr($source, 0, strrpos($source, '@endsection'));
$checks = 0;
function layoutCheck(bool $ok, string $message): void
{
    global $checks;
    if (! $ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}
function renderProfile(array $bags = [], ?array $pending = null, array $old = []): DOMXPath
{
    global $source, $user, $session;
    $session->flash('_old_input', $old);
    $errors = new ViewErrorBag;
    foreach ($bags as $name => $messages) {
        $errors->put($name, new MessageBag($messages));
    }
    $html = Blade::render($source, ['user' => $user, 'errors' => $errors, 'emailChangeSupported' => true, 'pendingEmailChange' => $pending]);
    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return new DOMXPath($dom);
}
$dom = renderProfile();
layoutCheck($dom->query('//*[@role="tab"]')->length === 3 && $dom->query('//*[@role="tabpanel" and not(@hidden)]')->length === 1, 'Exactly three tabs and one visible settings panel');
layoutCheck($dom->query('//*[@id="profile-tab-personal" and @aria-selected="true"]')->length === 1, 'Personal Information opens by default');
layoutCheck($dom->query('//header//*[contains(text(), "MS")]')->length === 1 && $dom->query('//header//*[contains(text(), "Maria Santos")]')->length === 1, 'Overview uses actual name and initials');
layoutCheck($dom->query('//header//*[contains(text(), "Active")]')->length === 1, 'Overview shows existing account status');
layoutCheck($dom->query('//input[@id="profileNewEmail" and @value=""]')->length === 1, 'New email never defaults to current address');
foreach (['profile.name.update' => 'PUT', 'profile.email.request' => null, 'profile.password.update' => 'PUT'] as $route => $method) {
    $form = $dom->query('//form[@action="'.route($route).'"]')->item(0);
    layoutCheck($form !== null && $dom->query('.//input[@name="_token"]', $form)->length === 1, 'Original handler and CSRF preserved: '.$route);
    if ($method) {
        layoutCheck($dom->query('.//input[@name="_method" and @value="'.$method.'"]', $form)->length === 1, 'Original HTTP method preserved');
    }
}
layoutCheck($dom->query('//input[@name="role" or @name="permissions" or @name="institution_ids"]')->length === 0, 'No editable privileged fields');
layoutCheck($dom->query('//aside[@aria-labelledby="profileOverviewHeading"]//dd[contains(., "None assigned")]')->length === 1, 'Empty institution assignments use an accurate read-only state');
layoutCheck($dom->query('//aside//input | //aside//form')->length === 0, 'Account Overview contains no editable or submitted fields');
layoutCheck($dom->query('//aside/ancestor::*[@role="tabpanel"]')->length === 0, 'Account Overview remains outside the switching tab panels');
layoutCheck($dom->query('//a[@href="'.route('legal.review').'"]')->length === 1 && $dom->query('//aside//a[@href="'.route('legal.review').'"]')->length === 1, 'Terms history appears once in the persistent overview');
$user->setRelation('institutions', collect([new Institution, new Institution, new Institution]));
$dom = renderProfile();
layoutCheck($dom->query('//aside//dd[contains(., "3 institutions")]')->length === 1, 'Assignment count reflects the supplied account relationship');
$user->setRelation('institutions', collect());
foreach (['profileName' => 'personal', 'profileEmail' => 'email', 'profileEmailConfirm' => 'email', 'default' => 'security'] as $bag => $tab) {
    $field = $bag === 'profileName' ? 'name' : ($bag === 'default' ? 'password' : 'email');
    $dom = renderProfile([$bag => [$field => 'Fixture validation error']], null, ['name' => 'Name draft', 'email' => 'draft@example.invalid', 'current_password' => 'never render', 'password' => 'never render']);
    layoutCheck($dom->query('//*[@id="profile-panel-'.$tab.'" and not(@hidden)]')->length === 1, 'Validation reveals correct panel: '.$bag);
    layoutCheck($dom->query('//*[@id="profile-panel-'.$tab.'"]//*[@role="alert" and contains(., "Fixture validation error")]')->length > 0, 'Validation message stays accessible');
    layoutCheck($dom->query('//input[@type="password" and @value]')->length === 0, 'Passwords are never repopulated');
    layoutCheck($dom->query('//input[@id="profileDisplayName"]')->item(0)->getAttribute('value') === ($bag === 'profileName' ? 'Name draft' : 'Maria Santos'), 'Name draft stays scoped to its form');
    layoutCheck($dom->query('//input[@id="profileNewEmail"]')->item(0)->getAttribute('value') === ($bag === 'profileEmail' ? 'draft@example.invalid' : ''), 'Email draft stays scoped to its form');
}
$dom = renderProfile([], ['email' => 'pending@example.invalid', 'expires_at' => time() + 300]);
layoutCheck($dom->query('//*[@id="profile-panel-email" and not(@hidden)]')->length === 1, 'Pending verification stays accessible');
layoutCheck($dom->query('//form[@action="'.route('profile.email.confirm').'"]//input[@name="code" and not(@value)]')->length === 1, 'Existing verification handler preserved without exposing code');
layoutCheck($dom->query('//*[@role="status" and contains(., "pending@example.invalid")]')->length === 1, 'Pending verification status remains readable');
echo "PASS: $checks Profile layout/rendering checks (synthetic data; no live requests).\n";
