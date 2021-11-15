<?php

namespace App\Services;

class MiscService
{   
    protected $cityTownFile;
    protected $bankCodeFile;

    public function __construct()
    {
        $this->cityTownFile = storage_path('misc/cityTown.json');
        $this->bankCodeFile = storage_path('misc/bankCode.json');
    }

    public function getCityTown()
    {
        $jsonString = file_get_contents($this->cityTownFile);
        return json_decode($jsonString, true);
    }

    public function getBankCode()
    {
        $jsonString = file_get_contents($this->bankCodeFile);
        return json_decode($jsonString, true);
    }
}