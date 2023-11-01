<?php

namespace res\server\controller\__front;

use Lay\libs\LayObject;
use res\server\utils\Email;

class EndUsers
{
    use \Lay\core\traits\IsSingleton;

    public function contact_us() : array {
        $post = LayObject::new()->get_json();

        if(
            Email::new()
                ->client($post->email, $post->name)
                ->subject("New Enquiry: " . $post->subject)
                ->body($post->message)
            ->to_server()
        )
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
