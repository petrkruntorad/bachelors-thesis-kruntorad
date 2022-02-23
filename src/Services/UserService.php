<?php

namespace App\Services;

use Symfony\Component\Uid\Uuid;

class UserService
{

    //generates random password
    function generatePassword(): string
    {
        return Uuid::v4()->toBase58();
    }
}
