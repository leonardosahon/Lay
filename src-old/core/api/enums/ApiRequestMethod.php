<?php

namespace Lay\core\api\enums;

enum ApiRequestMethod : string
{
    case POST = "POST";
    case GET = "GET";
    case HEAD = "HEAD";
    case PUT = "PUT";
    case DELETE = "DELETE";
}
