<?php

namespace Sam\ReqresUserService\DTO;

use JsonSerializable;

class UserDto implements JsonSerializable
{
    protected int $id;

    protected string $email;

    protected string $firstName;

    protected string $lastName;

    protected string $avatar;

    public function __construct(int $id, string $email, string $firstName, string $lastName, string $avatar)
    {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->avatar = $avatar;
    }

    public static function fromArray(mixed $data)
    {
        return new self(
            $data['id'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['avatar']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'avatar' => $this->avatar,
        ];
    }
}