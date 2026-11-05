<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('user_activity_sessions', function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id')->index();$t->string('user_name');$t->string('session_key',64)->unique();$t->string('channel',10);$t->timestamp('opened_at');$t->timestamp('last_seen_at')->index();$t->timestamp('closed_at')->nullable();});
  Schema::create('user_activity_logs', function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id')->index();$t->string('user_name');$t->string('action',20);$t->string('record_type');$t->string('record_id')->nullable();$t->text('changed_fields')->nullable();$t->timestamp('occurred_at')->index();});
 }
 public function down(): void {Schema::dropIfExists('user_activity_logs');Schema::dropIfExists('user_activity_sessions');}
};
