<?php

namespace res\server\utils;

use Lay\core\sockets\IsSingleton;
use Lay\libs\LayMail;

class Email extends LayMail
{
    use IsSingleton;

}
