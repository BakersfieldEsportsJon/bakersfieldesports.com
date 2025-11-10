<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('username');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('activity_type', ['login', 'logout', 'page_access', 'admin_action'])->nullable();
            $table->string('page_url')->nullable();
            $table->boolean('is_admin_session')->default(false);
            $table->json('location_data')->nullable();
            $table->timestamp('last_activity')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['session_id', 'activity_type']);
            $table->index('is_admin_session');
        });
    }

    public function down()
    {
        Schema::dropIfExists('active_sessions');
    }
}
