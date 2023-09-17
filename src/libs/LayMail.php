<?php
declare(strict_types=1);
namespace Lay\libs;

require_once \Lay\AutoLoader::instance()::get_root_dir() . "vendor/autoload.php";

use Lay\core\LayConfig;
use Lay\orm\SQL;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

abstract class LayMail {
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
        }catch (\Exception $e){
            \Lay\core\Exception::throw_exception("SMTP Credentials has not been setup. " . $e->getMessage(),"SMTPCredentialsError", stack_track: $e->getTrace());
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
     * <table>
     *   <tr>
     *      <th>email {string}</th>
     *      <th>name {string}</th>
     *      <th>subject {string} [optional]</th>
     *      <th>recipient {string} [optional]</th>
     *      <th>attachment {array} [optional]</th>
     *      <th>bcc {array} [optional]</th>
     *      <th>cc {array} [optional]</th>
     *   </tr>
     *   <tr>
     *      <td>Email of Recipient. (Serves as sender email when sending to server)</td>
     *      <td>Name of Recipient. (Serves as sender name when sending to server)</td>
     *      <td>Subject of email</td>
     *      <td>
     *          ["name","file" (absolute location to file using site base as the root link), "type" (MIME file type, empty by default)]
     *      </td>
     *      <td>By default recipient is `client`, pass `server` as key with value `true`, if you want to send to domain server; example: `'server' => true`</td>
     *      <td>[bcc email address, bcc recipient name]</td>
     *      <td>[cc email address, cc recipient name]</td>
     *   </tr>
     * </table>
     * @return bool|null returns true on successful
     * @throws Exception
     */
    final public static function queue(array|string $subject_or_opt, array $opt = []) : ?bool {
        if(!self::$credentials['host'])
            LayConfig::set_smtp();

        $site_data = LayConfig::instance()->get_site_data();
        $opt = is_array($subject_or_opt) ? array_merge($opt,$subject_or_opt) : $opt;

        $mail = new PHPMailer();
        $use_smtp = $opt['smtp'] ?? true;
        $email = $opt['email'] ?? null;
        $name = $opt['name'] ?? null;
        $site_mail = self::$credentials['default_sender_email'] ?? $site_data->mail->{0};
        $site_name = self::$credentials['default_sender_name'] ?? $site_data->name->short;
        $receiver = $opt['recipient'] ?? 'client';

        $recipient = [
            "to" => $email,
            "name" => $name
        ];

        if(isset($opt["server"]) || $receiver == 'server') {
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
                $attach['encoding'] ?? null,
                $attach['type'] ?? ''
            );
        }

        try {
            if($use_smtp)
                $mail = self::smtp_settings($mail, isset($opt['debug']));

            if(LayConfig::get_env() != "DEV" || isset($opt['force_dev']))
                return $mail->send();

            return true;

        } catch (\Exception $e) {
            \Lay\core\Exception::throw_exception(htmlspecialchars($recipient['to']) . ' LayMail.php' . $mail->ErrorInfo, "MailerError", false);
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