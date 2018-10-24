<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSourceFieldsToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('source_id')->unsigned()->nullable()->after('reference');
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
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_source_id_foreign');
            $table->dropColumn('source_id');
            $table->dropColumn('referer');
        });
    }
}
