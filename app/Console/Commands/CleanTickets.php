<?php namespace App\Console\Commands;

class CleanTickets extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:clean  {--simulate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sites = \App\Site::all();

        foreach ($sites as $site) {
            $this->info("[{$site->id}] {$site->title}");

            // Get users
            $users_ids = $site->users->pluck('id')->all();

            // Tickets extraviados
            $tickets = $site->tickets()->whereNotIn('user_id', $users_ids)->get();
            if ($tickets->count()) {
                foreach ($tickets as $ticket) {
                    $messages = $ticket->messages()->whereNotIn('user_id', $users_ids)->get();

                    if (!$this->option('simulate')) {
                        // release the messages
                        foreach ($messages as $message) {
                            $message->user_id = null;
                            $message->save();
                        }

                        // release the ticket
                        $ticket->user_id = null;
                        $ticket->save();
                    }
                }
            }

            $this->info('    -> '. $tickets->count());
        }
    }

}
