<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('site_id')->unsigned()->index();
            $table->string('email');
            $table->string('fullname');
            $table->string('company');
            $table->string('phone');
            $table->text('address');
            $table->integer('locale_id')->unsigned()->nullable()->index();
            $table->string('image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('site_id')
				->references('id')->on('sites')
				->onUpdate('cascade')
				->onDelete('cascade');

            $table->foreign('locale_id')
				->references('id')->on('locales')
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
        Schema::drop('contacts');
    }
}
