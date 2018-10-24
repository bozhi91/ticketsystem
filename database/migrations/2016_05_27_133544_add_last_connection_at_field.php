<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastConnectionAtField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->timestamp('last_connection_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn('last_connection_at');
        });
    }
}
