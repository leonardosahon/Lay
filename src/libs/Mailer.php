<?php
declare(strict_types=1);
namespace Lay\libs;

require_once \Lay\AutoLoader::instance()::get_root_dir() . "vendor/autoload.php";

use Lay\core\LayConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

abstract class Mailer {
    private static array $credentials = [
        "host" => null,
        "port" => null,
        "protocol" => null,
        "username" => null,
        "password" => null,
        "default_sender_email" => null,
        "default_sender_name" => null,
    ];
    private static function smtp_settings(PHPMailer $mail, bool $debug = false) : PHPMailer {
        if ($debug)
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;            //Enable verbose debug output

        $mail->isSMTP();                                      // Send using SMTP
        $mail->SMTPAuth   = true;                             // Enable SMTP authentication
        try {
            $mail->SMTPSecure = self::$credentials['protocol'];   // Enable implicit TLS encryption
            $mail->Host       = self::$credentials['host'];       // Set the SMTP server to send through
            $mail->Port       = self::$credentials['port'];       // use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            $mail->Username   = self::$credentials['username'];
            $mail->Password   = self::$credentials['password'];
        }catch (Exception $e){
            \Lay\core\Exception::throw_exception("SMTP Credentials has not been setup","MAILER::ERR");
        }
        return $mail;
    }
    final public static function get_credentials() : array {
        return self::$credentials;
    }
    final public static function set_credentials(array $details) : array {
        return self::$credentials = $details;
    }

    /**
     * @param array|string $subject_or_opt could serve as subject or option parameter,
     * @param array $opt [] the table heads serve as the 1st dimension keys
     * - **email {string}**
     * - **name {string}**
     * - **subject {string}** It becomes optional when the `$subject_or_opt` is a string
     * - **recipient {string}** [optional] `client` (default) | `server`. Use server when you want to send an email to the server.
     * - **attachment {array}** [optional] ["name","file" (absolute location to file using site base as the root link), "type" (MIME file type, empty by default)]
     * - **bcc {array}** [optional] [bcc email address, bcc recipient name]
     * - **cc {array}** [optional] [cc email address, cc recipient name]
     * @return bool|null returns true on successful
     * @throws Exception
     */
    final public static function queue(array|string $subject_or_opt, array $opt = []) : ?bool{
        $site_data = LayConfig::instance()->get_site_data();
        $opt = is_array($subject_or_opt) ? array_merge($opt,$subject_or_opt) : $opt;

        $mail = new PHPMailer();
        $use_smtp = $opt['smtp'] ?? true;
        $email = $opt['email'] ?? null;
        $name = $opt['name'] ?? null;
        $site_mail = self::$credentials['default_sender_email'] ?? $site_data->mail->{0};
        $site_name = self::$credentials['default_sender_name'] ?? $site_data->name->short;

        $recipient = [
            "to" => $email,
            "name" => $name
        ];

        if(isset($opt["server"])) {
            $recipient = [
                "to" => $site_mail,
                "name" => $site_name
            ];

            $mail->addReplyTo($email ?? $site_mail, $name ?? $site_name);
        }
        else
            $mail->addReplyTo($site_mail, $site_name);

        $mail->Subject = $opt['subject'] ?? $subject_or_opt;
        $mail->msgHTML(self::email_template($opt['message'] ?? $opt['msg']));

        $mail->addAddress($recipient['to'], $recipient['name']);
        $mail->setFrom($site_mail, $site_name);

        if(isset($opt['bcc']))
            foreach($opt['bcc'] as $bcc){
                $mail->addBCC($bcc[0], $bcc[1]);
            }

        if(isset($opt['cc']))
            foreach($opt['cc'] as $cc){
                $mail->addCC($cc[0], $cc[1]);
            }

        if(isset($opt['attachment'])) {
            $attach = $opt['attachment'];
            $mail->addStringAttachment(
                $attach['name'],
                $attach['file'],
                null,
                $attach['type'] ?? ''
            );
        }

        try {
            if($use_smtp)
                $mail = self::smtp_settings($mail, isset($opt['debug']));

            if(LayConfig::get_env() != "DEV" or isset($opt['force_dev']))
                return $mail->send();

            return true;

        } catch (\Exception $e) {
            \Lay\orm\SQL::instance()->use_exception("Mailer Error", htmlspecialchars($recipient['to']) . ' Mailer.php' . $mail->ErrorInfo, false);
            // Reset the connection to abort sending this message
            // If Loop the loop will continue trying to send to the rest of the list
            $mail->getSMTPInstance()->reset();
        }

        // If loop Clears all addresses and attachments for the next iteration
        $mail->clearAddresses();
        $mail->clearAttachments();

        return null;
    }

    public static function email_template(string $message) : string {
        $data = LayConfig::instance()->get_site_data();
        $logo = $data->img->logo;
        $company_name = $data->name->short;
        $copyright = $data->copy;
        $text_color = $data->color->pry;
        $bg_color = $data->color->sec;

        return <<<MSG
                <html lang="en"><body>
                    <div style="background: $bg_color; color: $text_color; padding: 20px; min-height: 400px">
                        <div style="text-align: center; background: $bg_color; padding: 10px 5px">
                            <img src="$logo" alt="$company_name Logo" style="max-width: 85%; padding: 10px; padding-bottom: 0">
                        </div>
                        <div style="
                            margin: 10px auto; 
                            padding: 15px 0; 
                            font-size: 16px; 
                            line-height: 1.6; 
                        ">$message</div>
                        <p style="text-align: center; font-size: 8px">$copyright</p>
                    </div>
                </body></html>
            MSG;
    }
}