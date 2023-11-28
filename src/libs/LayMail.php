<?php
declare(strict_types=1);
namespace Lay\libs;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\LayConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

abstract class LayMail {
    private static PHPMailer $mail_link;
    public const ENCODING_7BIT = PHPMailer::ENCODING_7BIT;
    public const ENCODING_8BIT = PHPMailer::ENCODING_8BIT;
    public const ENCODING_BASE64 = PHPMailer::ENCODING_BASE64;
    public const ENCODING_BINARY = PHPMailer::ENCODING_BINARY;
    public const ENCODING_QUOTED_PRINTABLE = PHPMailer::ENCODING_QUOTED_PRINTABLE;

    private static array $credentials = [
        "host" => null,
        "port" => null,
        "protocol" => null,
        "username" => null,
        "password" => null,
        "default_sender_email" => null,
        "default_sender_name" => null,
    ];

    private array $attachment;
    private array $client;
    private array $bcc;
    private array $cc;
    private array $server;
    private array $server_from;
    private string $body;
    private string $subject;
    private bool $to_client = true;
    private bool $use_smtp = true;
    private bool $debug = false;
    private bool $send_on_dev_env = false;

    private function connect_smtp() : void {
        if(!$this->use_smtp)
            return;

        if ($this->debug)
            self::$mail_link->SMTPDebug = SMTP::DEBUG_SERVER;            //Enable verbose debug output

        self::$mail_link->isSMTP();                                      // Send using SMTP
        self::$mail_link->SMTPAuth   = true;                             // Enable SMTP authentication
        try {
            self::$mail_link->SMTPSecure = self::$credentials['protocol'];   // Enable implicit TLS encryption
            self::$mail_link->Host       = self::$credentials['host'];       // Set the SMTP server to send through
            self::$mail_link->Port       = self::$credentials['port'];       // use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            self::$mail_link->Username   = self::$credentials['username'];
            self::$mail_link->Password   = self::$credentials['password'];
        }catch (\Exception $e){
            \Lay\core\Exception::throw_exception("SMTP Credentials has not been setup. " . $e->getMessage(),"SMTPCredentialsError", stack_track: $e->getTrace());
        }

    }

    final public static function get_credentials() : array {
        return self::$credentials;
    }

    final public static function set_credentials(array $details) : array {
        return self::$credentials = $details;
    }

    final public function client(string $email, string $name) : self {
        $this->client = ["email" => $email, "name" => $name];
        return $this;
    }

    final public function bcc(#[ArrayShape(['email' => 'string','name' => 'string'])] array ...$bcc) : self {
        $this->bcc = $bcc;
        return $this;
    }

    final public function cc(#[ArrayShape(['email' => 'string','name' => 'string'])] array ...$cc) : self {
        $this->cc = $cc;
        return $this;
    }

    final public function server(string $email, string $name) : self {
        $this->server = ["email" => $email, "name" => $name];
        return $this;
    }

    final public function server_from(string $email, string $name) : self {
        $this->server_from = ["email" => $email, "name" => $name];
        return $this;
    }

    final public function body(string $email_body) : self {
        $this->body = $email_body;
        return $this;
    }

    final public function attachment(
        string $filename,
        string $string_or_name = null,
        string $type = '',
        #[ExpectedValues(valuesFromClass: LayMail::class)] string $encoding = self::ENCODING_BASE64,
        string $disposition = "attachment",
        bool $attach_as_string = true
    ) : self {
        $this->attachment = [
            "data" => $string_or_name,
            "filename" => $filename,
            "type" => $type,
            "encoding" => $encoding,
            "disposition" => $disposition,
            "as_string" => $attach_as_string,
        ];
        return $this;
    }

    final public function subject(string $email_subject) : self {
        $this->subject = $email_subject;
        return $this;
    }

    /**
     * Sends the email to the server, rather than the client.
     * @return bool The result of the queued email
     */
    final public function to_server() : bool {
        $this->to_client = false;
        return $this->queue();
    }


    /**
     * Sends the email to the client. This is the default behaviour
     * @return bool The result of the queued email
     */
    final public function to_client() : bool {
        $this->to_client = true;
        return $this->queue();
    }

    /**
     * Disable the use of smtp connection, maybe if you want to use the local mail server
     * @return $this
     */
    final public function not_smtp() : self {
        $this->use_smtp = false;
        return $this;
    }

    /**
     * Force the mail server to send an email on localserver
     * @return $this
     */
    final public function send_on_dev_env() : self {
        $this->send_on_dev_env = true;
        return $this;
    }

    final public function queue() : ?bool {
        if(!self::$credentials['host'])
            LayConfig::set_smtp();

        $site_data = LayConfig::new()::site_data();

        self::$mail_link = new PHPMailer();
        $email = $this->client['email'] ?? null;
        $name = $this->client['name'] ?? null;

        if((empty($email) || empty($name)) && $this->to_client)
            \Lay\core\Exception::throw_exception("Sending an email <b>to a client</b> with an empty `email`: [$email] or `name`: [$name] is not allowed!. If you wish to send to the server, use `->to_server()` method.", "EmptyRequiredField");

        $server_mail_from = $this->server_from->mail ?? self::$credentials['default_sender_email'] ?? $site_data->mail->{0};
        $server_name_from = $this->server_from->name ?? self::$credentials['default_sender_name'] ?? $site_data->name->short;

        $server_mail_to = $this->server->mail ?? $site_data->mail->{0};
        $server_name_to = $this->server->name ?? $site_data->name->short;

        if($this->to_client) {
            $recipient = [
                "to" => $email,
                "name" => $name
            ];

            self::$mail_link->addReplyTo($server_mail_to, $server_name_to);
        }
        else {
            $recipient = [
                "to" => $server_mail_to,
                "name" => $server_name_to
            ];

            self::$mail_link->addReplyTo($email ?? $server_mail_to, $name ?? $server_name_to);
        }

        if(@empty($this->subject))
            \Lay\core\Exception::throw_exception("Sending an email with an empty `subject` is not allowed!", "EmptyRequiredField");

        self::$mail_link->Subject = $this->subject;

        if(@empty($this->body))
            \Lay\core\Exception::throw_exception("Sending an email with an empty `body` is not allowed!", "EmptyRequiredField");

        self::$mail_link->msgHTML($this->email_template($this->body));

        self::$mail_link->addAddress($recipient['to'], $recipient['name']);
        self::$mail_link->setFrom($server_mail_from, $server_name_from);

        if(isset($this->bcc))
            foreach($this->bcc as $bcc) {
                self::$mail_link->addBCC($bcc['email'], $bcc['name']);
            }

        if(isset($this->cc))
            foreach($this->cc as $cc) {
                self::$mail_link->addCC($cc['email'], $cc['name']);
            }

        if(isset($this->attachment)) {
            if($this->attachment['as_string'])
                self::$mail_link->addStringAttachment(
                    $this->attachment['data'],
                    $this->attachment['filename'],
                    $this->attachment['encoding'],
                    $this->attachment['type'],
                    $this->attachment['disposition'],
                );
            else {
                if(!file_exists($this->attachment['filename']))
                    \Lay\core\Exception::throw_exception("The file you're trying to attach does not exist", "AttachmentNotFound");

                self::$mail_link->addAttachment(
                    $this->attachment['filename'],
                    $this->attachment['data'],
                    $this->attachment['encoding'],
                    $this->attachment['type'],
                    $this->attachment['disposition'],
                );
            }
        }

        try {
            $this->connect_smtp();

            if(LayConfig::$ENV_IS_PROD || $this->send_on_dev_env)
                return self::$mail_link->send();

            return true;

        } catch (\Exception) {
            \Lay\core\Exception::throw_exception(htmlspecialchars($recipient['to']) . ' LayMail.php' . self::$mail_link->ErrorInfo, "MailerError", false);
            // Reset the connection to abort sending this message
            // If Loop the loop will continue trying to send to the rest of the list
            self::$mail_link->getSMTPInstance()->reset();
        }

        // If loop Clears all addresses and attachments for the next iteration
        self::$mail_link->clearAddresses();
        self::$mail_link->clearAttachments();
        self::$mail_link->clearBCCs();
        self::$mail_link->clearCCs();

        return null;
    }

    public function email_template(string $message) : string {
        $data = LayConfig::site_data();
        $logo = $data->img->logo;
        $company_name = $data->name->short;
        $copyright = $data->copy;
        $text_color = "#000000";
        $bg_color = "transparent";

        return <<<MSG
                <html lang="en"><body>
                    <div style="background: $bg_color; color: $text_color; padding: 20px; min-height: 400px; max-width: 80%; margin: auto">
                        <div style="text-align: center; background: $bg_color; padding: 10px 5px">
                            <img src="$logo" alt="$company_name Logo" style="max-width: 85%; padding: 10px 10px 0">
                        </div>
                        <div style="
                            margin: 10px auto;
                            padding: 15px 0;
                            font-size: 16px;
                            line-height: 1.6;
                        ">$message</div>
                        <p style="text-align: center; font-size: 12px">$copyright</p>
                    </div>
                </body></html>
            MSG;
    }
}
