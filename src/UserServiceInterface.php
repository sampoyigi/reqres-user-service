<?php

namespace Sam\ReqresUserService;

use Sam\ReqresUserService\DTO\UserDto;

interface UserServiceInterface
{
    public function getUserById(int $id): ?UserDto;

    public function getPaginatedUsers(int $page): array;

    public function createUser(string $name, string $job): int;
}
