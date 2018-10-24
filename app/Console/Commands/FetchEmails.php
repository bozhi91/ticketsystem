<?php namespace App\Console\Commands;

class FetchEmails extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from emails accounts.';

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
	try {

        // Retrieve the sites to check
        $sites = \App\Site::all();
        foreach ($sites as $site)
        {
            $this->info("Fetching emails for site [{$site->id}]: {$site->title}");

            // Retrieve site fetch account
            $account = $site->fetchAccount();
            if (!$account)
            {
                $this->error('No valid credentials defined.');
                continue;
            }

            try
            {
                // Read all messages into an array
                $mailsIds = $account->fetchNewEmails();
                //$mailsIds = $account->searchMailbox('SUBJECT "Fwd: idealista solicita información"');
            }
            catch (\Exception $e)
            {
                $this->error($e->getMessage());
                $account->setError($e->getMessage());
                continue;
            }

            // Get the first message and save its attachment(s) to disk:
            foreach ($mailsIds as $id)
            {
                $mail = $account->getMail($id);
                $this->info($id.' -> '.$mail->getMessageId());

                $attachments = [];
                if (!empty($mail->getAttachments()))
                {
                    foreach ($mail->getAttachments() as $attach)
                    {
                        $attachments [] = [
                            'key' => $attach->id,
                            'title' => $attach->name,
                            'filename' => preg_replace('#^'.storage_path().'#', '', $attach->filePath),
                            'description' => $attach->disposition
                        ];
                    }
                }

                // Check if email is blacklisted
                if (\App\EmailBlacklist::blacklisted($mail)) {
                    $this->info('Blacklisted [SKIP]');
                    $this->remove_attachments($attachments);
                    continue;
                }

                // Parse email data
                $parsed = \App\EmailParser::parse($mail);
                $email_from = $parsed ? $parsed->fromAddress : $mail->fromAddress;
                $email_from_name = $parsed ? $parsed->fromName : $mail->fromName;

                // Reset ticket variable
                $ticket = null;

                // Check if the message is allready imported
                $message = \App\Message::where('message_id', $mail->getMessageId())->first();
                if ($message)
                {
                    $this->info('Message exists [SKIP]');
                    $this->remove_attachments($attachments);
                    continue;
                }

                // Find ticket using In-Reply-To header
                if ($mail->getInReplyTo())
                {
                    $reply = \App\Email::where('email_id', $mail->getInReplyTo())->whereNotNull('message_id')->first();
                    if ($reply)
                    {
                        $ticket = $reply->message->ticket;
                    }
                }

                // If we have ticket, we create a new message to the ticket
                if ($ticket)
                {
                    // Attach the email to the ticket
                    $message = $ticket->addContactMessage([
                        'source' => 'email',
                        'subject' => $mail->getSubject(),
                        'body' => $mail->getContent(),
                        'message_id' => $mail->getMessageId(),
                        'attachments' => $attachments,
                        'referer' => ($parsed && !empty($parsed->referer)) ? $parsed->referer : null,
                    ]);

                    // Notify the user by email
                    $message->notifyUser();
                }
                else
                // If not, check if we need to create the new ticket
                {
                    $create_ticket = false;

                    // If the contact exists in the account, we create a new ticket
                    $contact = $site->contacts()->whereEmail($email_from)->first();
                    if ($contact)
                    {
                        $create_ticket = true;
                    }
                    else
                    {
                        $data = [
                            'site_id' => $site->id,
                            'email' => $email_from,
                            'fullname' => $email_from_name,
                            'referer' => ($parsed && !empty($parsed->referer)) ? $parsed->referer : 'ticket',
                            'phone' => ($parsed && !empty($parsed->phone)) ? $parsed->phone : null
                        ];

                        $validator = \App\Contact::getValidator($data);
                        if ($validator->fails())
                        {
                            $this->error('Invalid contact data ['.json_encode($validator->errors()).']: '.json_encode($data));
                            continue;
                        }

                        $contact = \App\Contact::saveModel($data);
                        if (!$contact)
                        {
                            $this->error('Error creating the contact: '.json_encode($data));
                            continue;
                        }

                        $create_ticket = true;
                    }

                    if ($create_ticket) {

                        // Create the ticket
                        try
                        {
                            $ticket = \App\Ticket::create([
                                'site_id' => $site->id,
                                'contact_id' => $contact->id,
                                'subject' => $mail->getSubject(),
                                'body' => $mail->getContent(),
                                'source' => 'email',
                                'message_id' => $mail->getMessageId(),
                                'attachments' => $attachments,
                                'referer' => ($parsed && !empty($parsed->referer)) ? $parsed->referer : 'ticket',
                            ]);
                        }
                        catch (\Illuminate\Validation\ValidationException $e)
                        {
                            $this->error($e->validator->errors());
                        }
                        catch (\Exception $e)
                        {
                            $this->error($e);
                        }
                    } else {
                        $this->remove_attachments($attachments);
                        $this->error('Unrelated email [SKIP]');
                    }
                }
            }
        }
	
	} catch (\Exception $e) {
		$this->error($e->getMessage());
	}

    }

    private function remove_attachments($attachemnts)
    {
        // Remove the attachments if we no create the ticket
        if (!empty($attachments)) {
            foreach ($attachments as $attach) {
                @unlink(storage_path().$attach['filename']);
            }
        }
    }

}
