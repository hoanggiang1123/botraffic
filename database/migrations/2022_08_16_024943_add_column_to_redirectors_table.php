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
            $table->integer('safe_redirect')->default(0);
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('description')->nullable();
            $table->string('keywords')->nullable();
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
            $table->dropColumn('safe_redirect');
            $table->dropColumn('title');
            $table->dropColumn('image');
            $table->dropColumn('description');
            $table->dropColumn('keywords');
        });
    }
};
