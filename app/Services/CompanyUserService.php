<?php

namespace App\Services;

use App\Models\CompanyUser;

class CompanyUserService
{
    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }

    public function getCompanyByUserId($userId)
    {
        $companyUserData = $this->companyUser->select('company_id')->where('user_id', $userId)->first();
        return $companyUserData->company_id;
    }
}