<?php

namespace App\Services;

use App\Models\User;

class CompanyService
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function update($request)
    {
        $user = $this->user->find($request->id);
        $user->update($request->all());

        return $user;
    }

}