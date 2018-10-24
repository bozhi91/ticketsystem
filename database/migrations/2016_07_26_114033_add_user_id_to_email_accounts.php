<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdToEmailAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->unsigned()->after('model_type');

            $table->foreign('user_id')
				->references('id')->on('users')
				->onUpdate('cascade')
				->onDelete('cascade');
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
            $table->dropForeign('email_accounts_user_id_foreign');
            $table->dropColumn('user_id');
        });
    }
}
