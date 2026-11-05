<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class TrackUserActivity {
 public function handle(Request $request, Closure $next) {
  $user=$request->user();$key=$request->hasSession() ? $request->session()->get('_activity_session_key', $this->key($request)) : $this->key($request);$response=$next($request);
  if(!Schema::hasTable('user_activity_sessions')) return $response;
  if($user && $request->routeIs('logout','api.v1.auth.logout','legal.decline','api.v1.legal.decline')) {
   DB::table('user_activity_sessions')->where('session_key',$key)->where('user_id',$user->id)->update(['closed_at'=>now(),'last_seen_at'=>now()]);
   return $response;
  }
  if($request->routeIs('api.v1.auth.login')) return $response;
  $user=$request->user();
  if(!$user || $response->getStatusCode()>=400) return $response;
  $key=$this->key($request);
  if($request->hasSession()) $request->session()->put('_activity_session_key', $key);
  $row=DB::table('user_activity_sessions')->where('session_key',$key)->first();
  if(!$row) DB::table('user_activity_sessions')->insertOrIgnore(['user_id'=>$user->id,'user_name'=>$user->name,'session_key'=>$key,'channel'=>$request->hasSession()?'web':'api','opened_at'=>now(),'last_seen_at'=>now()]);
  else if(!$row->closed_at) DB::table('user_activity_sessions')->where('id',$row->id)->update(['last_seen_at'=>now()]);
  return $response;
 }
 private function key(Request $request): string {
  $token=$request->user()?->currentAccessToken();
  return hash('sha256',$request->hasSession()?'web:'.$request->session()->getId():'api:'.($token?->id ?? 'unknown').':'.($request->user()?->id ?? 'guest'));
 }
}
