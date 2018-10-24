<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\FetchEmails::class,
        Commands\CleanAttachments::class,
        Commands\CleanTickets::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('emails:fetch')
			->everyTenMinutes()
			->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/emails:fetch-'.date('Y-m-d').'.log'))
            ->after(function()
            {
                // TODO: remove old logs

                // Notify the execution of the command
                $heartbeat = env('EMAILS_FETCH_HEARTBEAT');
                if (filter_var($heartbeat, FILTER_VALIDATE_URL)) {
                    $client = new \GuzzleHttp\Client;
                    $response = $client->request('GET', $heartbeat);
                    if ($response->getStatusCode() != 200) {
                        throw new \UnexpectedValueException("EMAILS_FETCH_HEARTBEAT ({$heartbeat}) is not responding. Response code: ". $response->getStatusCode());
                    }
                }
            });

        $schedule->command('attachments:clean')->dailyAt('01:00');
    }
}
