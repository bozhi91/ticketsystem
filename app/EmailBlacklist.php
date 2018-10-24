<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailBlacklist extends Model
{
    public static function blacklisted(\App\PhpImap\IncomingMail $mail)
    {
        // Get all blacklisted rules
        $rules = self::where('enabled', 1)->get(['subject', 'email_from'])->toArray();

        foreach ($rules as $rule) {
            $blacklisted = 0;

            // we count the number of fields to take in mind
            foreach ($rule as $value) {
                if (!empty($value)) {
                    $blacklisted++;
                }
            }

            // error, nothing to check
            if (!$blacklisted) {
                continue;
            }

            foreach ($rule as $field => $value) {
                if (empty($field)) continue;

                switch ($field) {
                    case 'email_from':
                        $check = $mail->fromAddress;
                        break;
                    case 'subject':
                        $check = $mail->getSubject();
                        break;
                }

                if ($check == $value) {
                    $blacklisted--;
                }
            }

            // If we have 0 points, then the mail is blacklisted
            if (!$blacklisted) {
                return true;
            }
        }

        return false;
    }

}
