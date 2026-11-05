<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserActivityController extends Controller {
 public function index(Request $request) {
  $request->validate(['search' => ['nullable', 'string', 'max:255']]);
  return view('admin.users.logs', $this->activityData(trim((string) $request->query('search', ''))));
 }
 public function activityData(string $search = ''): array {
  $sessions=DB::table('user_activity_sessions')->when($search!=='',fn($q)=>$q->where('user_name','like','%'.$search.'%'))->orderByDesc('last_seen_at')->paginate(15,['*'],'sessions_page')->withQueryString();
  $logs=DB::table('user_activity_logs')->when($search!=='',fn($q)=>$q->where('user_name','like','%'.$search.'%'))->orderByDesc('occurred_at')->paginate(20,['*'],'logs_page')->withQueryString();
  $online=DB::table('user_activity_sessions')->whereNull('closed_at')->where('last_seen_at','>=',now()->subMinutes(2))->distinct()->count('user_id');
  return compact('sessions','logs','online');
 }
 public function heartbeat(){return response()->noContent();}
}
 