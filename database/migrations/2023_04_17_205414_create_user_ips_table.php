<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('hostname')->nullable();
            $table->string('link')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('is_cookies')->nullable();
            $table->string('screen')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->timestamps();
            $table->index('ip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_ips');
    }
};
