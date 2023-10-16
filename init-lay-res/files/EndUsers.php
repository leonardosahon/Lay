<?php

namespace res\server\controller\__front;

use Lay\core\LayConfig;
use Lay\libs\LayMail;
use Lay\libs\LayObject;

class EndUsers
{
    use \Lay\core\sockets\IsSingleton;

    public function contact_us() : array {
        $post = LayObject::new()->get_json();

        LayConfig::set_smtp();

        if(LayMail::queue([
            "name" => $post->name,
            "email" => $post->email,
            "message" => $post->message,
            "subject" => "New Enquiry On The Website",
            "server" => true,
        ]))
            return [
                "code" => 1,
                "msg" => "Your enquiry has been sent and a response will be given accordingly, please ensure to check your email for a response"
            ];

        return [
            "code" => 0,
            "msg" => "Cannot contact us at the moment, please try again later"
        ];
    }
}
