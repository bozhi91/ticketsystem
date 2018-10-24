<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('email_account_id')->nullable()->unsigned();
            $table->bigInteger('message_id')->unsigned()->nullable();
            $table->string('to');
            $table->string('subject');
            $table->text('body');
            $table->text('attachments');
            $table->text('headers');
            $table->string('email_id')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->text('error');
            $table->timestamps();

            $table->foreign('email_account_id')
                ->references('id')->on('email_accounts')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('id')->on('messages')
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
        Schema::drop('emails');
    }
}
