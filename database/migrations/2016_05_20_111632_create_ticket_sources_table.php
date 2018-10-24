<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_sources', function (Blueprint $table) {
            Schema::create('ticket_sources', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->string('name');
                $table->integer('sort')->default(0);
                $table->timestamps();
            });

            $statuses = [
                ['code' => 'email', 'name' => 'E-mail'],
                ['code' => 'phone', 'name' => 'Phone'],
                ['code' => 'web', 'name' => 'Website'],
                ['code' => 'chat', 'name' => 'Chat'],
                ['code' => 'facebook', 'name' => 'Facebook'],
                ['code' => 'backoffice', 'name' => 'Backoffice'],
                ['code' => 'other', 'name' => 'Other']

            ];

            foreach ($statuses as $i => $item)
            {
                $item['sort'] = $i;
                \App\TicketSource::create($item);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_sources', function (Blueprint $table) {
            Schema::drop('ticket_sources');
        });
    }
}
