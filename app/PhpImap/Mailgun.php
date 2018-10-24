<?php namespace App\PhpImap;

use stdClass;

class Mailgun {

	protected $attachmentsDir;

	protected $domain;
	protected $apiKey;

	protected $client;
	protected $mailgun;

	public function __construct($domain, $apiKey, $attachmentsDir = null)
	{
		$this->domain = $domain;
		$this->apiKey = $apiKey;
		if ($attachmentsDir)
		{
			if (!is_dir($attachmentsDir))
			{
				throw new \UnexpectedValueException('Directory "' . $attachmentsDir . '" not found');
			}
			$this->attachmentsDir = rtrim(realpath($attachmentsDir), '\\/');
		}

		$this->client = new \Http\Adapter\Guzzle6\Client();
	    $this->mailgun = new \Mailgun\Mailgun($this->apiKey, $this->client);
	}

	/**
	 * Test the connection.
	 *
	 * @return boolean
	 */
	public function testConnection()
	{
		$response = $this->mailgun->get($this->domain.'/log', ['limit' => 1]);
		if ($response->http_response_code != 200)
		{
			throw new \UnexpectedValueException("Error connecting with the server: {$response->http_response_code} ");
		}

		return true;
	}

	/**
	 * Search Mailgun log for emails that match the criteria.
	 * The criteria is mapped from the imap_search options to adapt mailgun API.
	 * @param  string $criteria imap_search string
	 * @return array           emails ids that can be used in getMail($id)
	 */
	public function searchMailbox($criteria = 'ALL')
	{
		$filters = [
	        'limit' => 300,
	        'event' => 'stored',
	        'ascending' => 'yes'
	    ];

		// SINCE "date"
		if (preg_match('#SINCE (\d{1,2}-\w{3}-\d{4})#', $criteria, $match))
		{
			$filters['begin'] = \Carbon\Carbon::createFromFormat('j-M-Y', $match[1])->subHours(24)->toRfc2822String();
		}

		// Fetch the data from the server
	    $response = $this->mailgun->get($this->domain.'/events', $filters);

	    $ids = [];
	    foreach ($response->http_response_body->items as $item)
	    {
	        $ids []= $item->storage->key;
	    }

		return $ids;
	}

    /**
     * Get mail data
     *
     * @param $mailId
     * @return IncomingMail
     */
	public function getMail($id)
	{
		try
		{
			$response = $this->mailgun->get('domains/'.$this->domain.'/messages/'.$id)->http_response_body;
		}
		catch (\Exception $e)
		{
			\Log::error($e);
			return false;
		}

		$mail = new IncomingMail();
		$mail->id = $id;
		$mail->date = date('Y-m-d H:i:s', isset($response->Date) ? strtotime(preg_replace('/\(.*?\)/', '', $response->Date)) : time());
		$mail->subject = $response->Subject;

		// From
		$from = $this->splitEmailAddress($response->From);
		$mail->fromAddress = $from['email'];
		$mail->fromName = $from['name'];

		$mail->toString = $response->To;
		$mail->to = $this->mailsStringToArray($response->To);

		if (isset($response->Cc))
		{
			$mail->cc = $this->mailsStringToArray($response->Cc);
		}

		if (isset($response->{'Reply-To'}))
		{
			$mail->cc = $this->mailsStringToArray($response->{'Reply-To'});
		}

		if (isset($response->{'In-Reply-To'}))
		{
			$mail->inReplyTo = $response->{'In-Reply-To'};
		}

		if (isset($response->{'Message-Id'}))
		{
			$mail->messageId = $response->{'Message-Id'};
		}

		if (isset($response->{'References'}))
		{
			$mail->references = $response->{'References'};
		}

		$mail->textPlain = $response->{'body-plain'};
		$mail->textHtml = $response->{'body-html'};

		// Attachments
		if (!empty($response->attachments))
		{
			$attachments = [];
			foreach ($response->attachments as $attach)
			{
				$attachment = new IncomingMailAttachment;
				$attachment->id = $attach->url;
				$attachment->name = $attach->name;
				$attachment->filePath = $this->attachmentsDir . DIRECTORY_SEPARATOR . uniqid().'_'.$attach->name;
				$attachment->disposition = 'attachment';

				// fetch the attachment
				$file_content = $this->mailgun->get($this->fixMailgunUrl($attach->url));
				if (!isset($file_content->http_response_body))
				{
					throw new \UnexpectedValueException("Error getting the attachment content");
				}
				file_put_contents($attachment->filePath, $file_content->http_response_body);

				$attachments []= $attachment;
			}

			// Check inline attachments by url
			if (!empty($response->{'content-id-map'}))
			{
				foreach ($response->{'content-id-map'} as $attachment_id => $attachment)
				{
					foreach ($attachments as $i => $attach)
					{
						if ($attach->id == $attachment->url)
						{
							$attachments[$i]->disposition = 'inline';
							$attachments[$i]->id = preg_replace('#[<>]+#', '', $attachment_id);
						}
					}
				}
			}

			// Fix ids and add to the mail
			foreach ($attachments as $attachment)
			{
				if (preg_match('#messages\/([\w]+)#', $attachment->id, $match))
				{
					$attachment->id = $match[1];
				}

				$mail->addAttachment($attachment);
			}
		}

		return $mail;
	}

	protected function getHeader($headers, $key)
	{
		foreach ($headers as $header)
		{
			if ($header[0] == $key)
			{
				return $header[1];
			}
		}

		return false;
	}

	protected function mailsStringToArray($string)
	{
		$response = [];
		$mails = explode(', ', $string);
		foreach ($mails as $mail)
		{
			$address = $this->splitEmailAddress($mail);
			$response[$address['email']] = $address['name'];
		}

		return $response;
	}

	protected function splitEmailAddress($address)
	{
		$split = explode('<', $address);

		// "Name" <email>
		if (isset($split[1]))
		{
			$email = [
				'name' => trim(preg_replace('#"+#', '', $split[0])),
				'email' => trim($split[1])
			];
		}
		else
		// email
		{
			$email = [
				'name' => null,
				'email' => trim($split[0])
			];
		}

		$email['email'] = preg_replace('#[<>]+#', '', $email['email']);

		return $email;
	}

	public function fixMailgunUrl($url)
	{
		$parts = explode($this->domain, $url);
		if (!empty($parts[1]))
		{
			$url = 'domains/'.$this->domain.$parts[1];
		}

		return $url;
	}

}
