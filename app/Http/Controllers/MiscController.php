<?php

namespace App\Http\Controllers;

use App\Services\MiscService;

class MiscController extends Controller
{
    private $miscService;

    public function __construct(MiscService $miscService)
    {
        $this->middleware('auth');
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
}