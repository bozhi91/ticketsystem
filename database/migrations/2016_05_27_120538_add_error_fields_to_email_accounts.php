<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddErrorFieldsToEmailAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->text('error')->nullable();
            $table->timestamp('error_at')->nullable();
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
            $table->dropColumn('error');
            $table->dropColumn('error_at');
        });
    }
}
