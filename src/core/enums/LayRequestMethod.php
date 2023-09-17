<?php

namespace Lay\core\enums;

enum LayRequestMethod : string
{
    case POST = "POST";
    case GET = "GET";
    case HEAD = "HEAD";
    case PUT = "PUT";
    case DELETE = "DELETE";
}
