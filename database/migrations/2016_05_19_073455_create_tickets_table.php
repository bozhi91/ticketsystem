<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('site_id')->unsigned()->index();
            $table->bigInteger('contact_id')->unsigned()->index();
            $table->bigInteger('user_id')->unsigned()->nullable()->index();
            $table->integer('reference');
            $table->timestamps();

            $table->foreign('site_id')
				->references('id')->on('sites')
				->onUpdate('cascade')
				->onDelete('cascade');

            $table->foreign('contact_id')
				->references('id')->on('contacts')
				->onUpdate('cascade')
				->onDelete('restrict');

            $table->foreign('user_id')
				->references('id')->on('users')
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
        Schema::drop('tickets');
    }
}
