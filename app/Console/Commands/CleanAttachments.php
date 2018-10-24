<?php namespace App\Console\Commands;

class CleanAttachments extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachments:clean {--simulate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes unused attachments.';

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
        $total_delete = $total_keep = 0;

        $path = storage_path('emails/inbox').'/';
        $files = glob($path.'*');

        foreach ($files as $file) {
            $filename = basename($file);
            if (\DB::table('filer_local_files')->where('filename', $filename)->first()) {
                $total_keep++;
                continue;
            }

            $total_delete++;
            if (!$this->option('simulate')) {
                if (unlink($file)){
                    $this->line('DELETED: '.$file);
                } else {
                    $this->error('ERROR: '.$file);
                }
            }
        }

        $this->info('Total delete: '.$total_delete);
        $this->info('Total keep: '.$total_keep);
    }

}
