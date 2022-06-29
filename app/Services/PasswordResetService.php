<?php

namespace App\Services;

use App\Models\PasswordReset;
use App\Models\User;

class PasswordResetService
{
    public function __construct(PasswordReset $passwordreset, User $user)
    {
        $this->model = $passwordreset;
        $this->user = $user;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function getById($id)
    {
        return $this->model->find($id);
    }

    public function create($request)
    {
        return $this->model->create($request);
    }

    public function getEmailAndToken($filter)
    {
        return $this->model
            ->where('signature', $filter['signature'])
            ->where('email', $filter['email'])
            ->first();
        // where('email', $filter['email']);
    }

    public function update_user($request)
    { // 此處用email作為判斷
        $user = $this->user->where('email', $request['email'])->first();
        unset($user['id']);
        $user->update($request);
        return $user;
    }

    public function update_is_password_change($request)
    {
        $user = $this->model->where('signature', $request['signature'])->first();
        unset($user['signature']);
        $user['is_password_change'] = $request['is_password_change'];
        $user->update($request);
        return response()->json(['success' => "修改完成!請重新登入"]);
    }

}
