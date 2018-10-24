<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStateFieldToTickeStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->string('state')->after('code');
        });

        $states = [
            'open' => 'open',
            'waiting' => 'open',
            'closed' => 'closed',
            'resolved' => 'closed'
        ];
        foreach ($states as $code => $state)
        {
            $status = \App\TicketStatus::where('code', $code)->first();
            if ($status)
            {
                $status->state = $state;
                $status->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
}
