<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_driver');
            $table->string('action');
            $table->string('request_method');
            $table->string('request_url');
            $table->text('request_headers')->nullable();
            $table->text('request_body')->nullable();
            $table->integer('response_status_code')->nullable();
            $table->text('response_headers')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_logs');
    }
};
