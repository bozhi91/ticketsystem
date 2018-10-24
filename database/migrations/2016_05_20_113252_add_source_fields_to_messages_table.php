<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSourceFieldsToMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->integer('source_id')->unsigned()->nullable()->after('user_id');
            $table->string('referer')->nullable()->after('source_id');

            $table->foreign('source_id')
				->references('id')->on('ticket_sources')
				->onUpdate('cascade')
				->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign('messages_source_id_foreign');
            $table->dropColumn('source_id');
            $table->dropColumn('referer');
        });
    }
}
