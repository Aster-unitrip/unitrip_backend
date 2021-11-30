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

    public function create($companyId, $userId)
    {
        try
        {
            return $this->companyUser->create(['company_id' => $companyId, 'user_id' => $userId])->toArray();
        }
        catch (\Exception $e)
        {
            return ['error' => $e->getMessage()];
        }
    }
}