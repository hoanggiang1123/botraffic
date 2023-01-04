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
        Schema::create('bad_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->integer('count')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->index(['ip', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bad_ips');
    }
};
