<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        $statuses = [
            ['code' => 'open', 'name' => 'Open'],
            ['code' => 'waiting', 'name' => 'Waiting'],
            ['code' => 'resolved', 'name' => 'Resolved'],
            ['code' => 'closed', 'name' => 'Closed']
        ];

        foreach ($statuses as $i => $item)
        {
            $item['sort'] = $i;
            \App\TicketStatus::create($item);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ticket_statuses');
    }
}
