<?php

namespace App\Services;

class MiscService
{   
    protected $cityTownFile;
    protected $bankCodeFile;
    protected $historicLevelFile;

    public function __construct()
    {
        $this->cityTownFile = storage_path('misc/cityTown.json');
        $this->bankCodeFile = storage_path('misc/bankCode.json');
        $this->historicLevelFile = storage_path('misc/historicLevel.json');
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

    public function getHistoricLevel()
    {
        $jsonString = file_get_contents($this->historicLevelFile);
        return json_decode($jsonString, true);
    }
}