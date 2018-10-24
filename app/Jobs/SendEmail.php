<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmail extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $email;
    protected $account;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(\App\Email $email, \App\EmailAccount $account)
    {
        $this->email = $email;
        $this->account = $account;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $account = $this->account;
        $email = $this->email;

        // Set the mailer with account credetials
        $old_mailer = \Mail::getSwiftMailer();
        \Mail::setSwiftMailer($account->getMailer());

        // Force the domain to molista (needed for gmail to work)
        \Mail::getSwiftMailer()->getTransport()->setLocalDomain('molista');

        // Email body
        $body = $email->body;

        // Replace inline attachments
        $attachments = $email->attachments;

        $params = [
            'from_email' => $account->from_email,
            'from_name' => $account->from_name,
            'to' => $email->to,
            'cc' => $email->cc,
            'bcc' => $email->bcc,
            'subject' => $email->subject,
            'body' => $body,
            'attachments' => $attachments
        ];

        // Last attempt
        $account->last_connection_at = \Carbon\Carbon::now();
        $account->save();

        // Check if contains the main html
        $is_html = preg_match('#<body#', $params['body']);

        $template = 'emails.default';
        if ($is_html)
        {
            $template = 'emails.html';
        }

        try
        {
            // Send email
            $res = \Mail::send($template, [ 'content' => $params['body'] ], function ($message) use ($params, &$message_id) {
                $message->from($params['from_email'], $params['from_name']);
                $message->to($params['to']);
                $message->subject($params['subject']);

                if (!empty($params['cc'])) {
                    $cc = is_array($params['cc']) ? $params['cc'] : explode(',', $params['cc']);
                    foreach ($cc as $e) {
                        $message->cc(trim($e));
                    }
                }

                if (!empty($params['bcc'])) {
                    $bcc = is_array($params['bcc']) ? $params['bcc'] : explode(',', $params['bcc']);
                    foreach ($bcc as $e) {
                        $message->bcc(trim($e));
                    }
                }

                // Get body for replace inline attachments
                $body = $message->getBody();

                if ( !empty($params['attachments']) )
                {
                    $attachments = $params['attachments'];

                    foreach ($attachments as $attachment)
                    {
                        $extra = [];
                        if (!empty($attachment['name']))
                        {
                            $extra['as'] = $attachment['name'];
                        }
                        if (!empty($attachment['mime']))
                        {
                            $extra['mime'] = $attachment['mime'];
                        }
                        if (!empty($attachment['id']))
                        {
                            $extra['id'] = $attachment['id'];
                        }

                        if (!empty($attachment['disposition']) && !empty($attachment['id']) && $attachment['disposition'] == 'inline')
                        {
                            $cid = 'cid:'.$attachment['id'];
                            $body = preg_replace('#'.$cid.'#', $message->embed($attachment['url']), $body);
                            continue;
                        }
                        else
                        {
                            // Attach!
                            $message->attach($attachment['url'], $extra);
                        }
                    }
                }

                // Set the final body
                $message->setBody($body);

                $message_id = $message->getId(); // Retrieve the ID for the sent email
            });
        }
        catch (\Exception $e)
        {
            $account->setError($e->getMessage());
            $email->setError($e->getMessage());
            return false;
        }
        finally
        {
            $failures = \Mail::failures();
            if (!empty($failures)) {
                return $email->setError('Error sending email to: '.implode(', ', $failures));
            }

            // Restore the mailer
            \Mail::setSwiftMailer($old_mailer);
        }

        // Guardar el message_id
        $email->sent($message_id);
    }
}
