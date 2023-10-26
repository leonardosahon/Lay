<?php

namespace Lay\tests\Unit;

use Lay\core\sockets\IsSingleton;

class FakeControllerSingleton
{
    use IsSingleton;
    public function print_user(int $id): array
    {
        return [
            "name" => "User name",
            "id" => $id
        ];
    }
}