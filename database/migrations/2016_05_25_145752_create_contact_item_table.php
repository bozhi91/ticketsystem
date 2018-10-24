<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('contact_id')->unsigned()->index();
            $table->bigInteger('item_id')->unsigned()->index();
            $table->timestamps();

            $table->unique(['contact_id', 'item_id']);

            $table->foreign('contact_id')
                ->references('id')->on('contacts')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('item_id')
				->references('id')->on('items')
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
        Schema::drop('contact_item');
    }
}
