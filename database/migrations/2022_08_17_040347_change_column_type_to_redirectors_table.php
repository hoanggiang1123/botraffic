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
        Schema::table('redirectors', function (Blueprint $table) {
            $table->text('description')->change();
            $table->text('title')->change();
            $table->text('keywords')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('redirectors', function (Blueprint $table) {
            $table->string('description')->change();
            $table->string('title')->change();
            $table->string('keywords')->change();
        });
    }
};
