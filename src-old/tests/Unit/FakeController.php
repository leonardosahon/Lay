<?php
declare(strict_types=1);

namespace Lay\tests\Unit;

class FakeController
{
    public function print_user(int $id, float $balance): array
    {
        return [
            "name" => "User name",
            "id" => $id,
            "balance" => $balance
        ];
    }

    /**
     * The test class calls this method and passes a string argument to the `$balance` parameter
     */
    public function test_wrong_type(int $id, float $balance): array
    {
        return [$id, $balance];
    }

    public function print_all(): array
    {
        return [
            ["name" => "User 1"],
            ["name" => "User 2"],
            ["name" => "User 3"],
        ];
    }

    public function print_something(): string
    {
        return "This is a stringed result";
    }

    public function print_user_wrong_type() : array {
        return null;
    }

    public function void_method() : void {

    }


}