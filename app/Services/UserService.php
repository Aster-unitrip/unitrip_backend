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
        $user = $this->user->find($request['user_id']);
        unset($user['user_id']);
        $user->update($request);

        return $user;
    }

}