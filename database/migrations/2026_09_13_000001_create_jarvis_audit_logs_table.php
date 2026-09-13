<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jarvis_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role', 20);           // 'admin' or 'public'
            $table->text('intent');                // what the user said / typed
            $table->json('payload')->nullable();   // full AI response
            $table->string('action_taken')->nullable(); // e.g. 'query_sales', 'add_to_cart'
            $table->integer('response_time_ms')->nullable(); // latency tracking
            $table->timestamps();

            $table->index('user_id');
            $table->index('role');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jarvis_audit_logs');
    }
};
