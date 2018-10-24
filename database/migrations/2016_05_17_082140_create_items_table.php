<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('site_id')->unsigned()->index();
            $table->string('reference');
            $table->string('type');
            $table->string('title');
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();

            $table->foreign('site_id')
				->references('id')->on('sites')
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
        Schema::drop('items');
    }
}
