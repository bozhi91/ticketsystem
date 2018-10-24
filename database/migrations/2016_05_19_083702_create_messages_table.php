<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('ticket_id')->unsigned()->index();
            $table->bigInteger('user_id')->unsigned()->nullable()->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->foreign('ticket_id')
				->references('id')->on('tickets')
				->onUpdate('cascade')
				->onDelete('cascade');

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
        Schema::drop('messages');
    }
}
