<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function update($request)
    {
        $user = $this->user->find($request['id']);
        unset($user['id']);
        $user->update($request);

        return $user;
    }

    public function getUserByEmail($email)
    {
        return $this->user->where('email', $email)->first();
    }

}