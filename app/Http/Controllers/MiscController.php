<?php

namespace App\Http\Controllers;

use App\Services\MiscService;
use App\Models\User;


class MiscController extends Controller
{
    private $miscService;

    public function __construct(MiscService $miscService)
    {
        // $this->middleware('auth');
        $this->miscService = $miscService;

    }


    public function cityTown()
    {
        return $this->miscService->getCityTown();
    }


    public function bankCode()
    {
        return $this->miscService->getBankCode();
    }

    public function historicLevel()
    {
        return $this->miscService->getHistoricLevel();
    }

    public function organization()
    {
        return $this->miscService->getOrganization();
    }

    public function nationality()
    {
        return $this->miscService->getNationality();
    }


    public function order_source()
    {
        return $this->miscService->getOrderSource();
    }

    public function company_employee()
    {
        // 所有資料傳回(未過濾)
        $user_company_id = auth()->user()->company_id;
        $same_company_users_data = User::where('company_id', $user_company_id)->get();
        return $same_company_users_data;
    }
}
