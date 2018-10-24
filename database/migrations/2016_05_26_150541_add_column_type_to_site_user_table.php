<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTypeToSiteUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_user', function (Blueprint $table) {
            $table->string('type', 20)->default('guest')->after('user_id');
        });

        $items = \DB::table('users')->select('id', 'type')->get();
        foreach ($items as $item)
        {
            \DB::table('site_user')->where('user_id', $item->id)->update(['type' => $item->type]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type', 20)->default('guest')->after('remember_token');
        });

        $items = \DB::table('site_user')->select('user_id', 'type')->get();
        foreach ($items as $item)
        {
            \DB::table('users')->where('id', $item->user_id)->update(['type' => $item->type]);
        }

        Schema::table('site_user', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
