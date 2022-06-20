<?php

namespace App\Services;

class MiscService
{
    protected $cityTownFile;
    protected $bankCodeFile;
    protected $historicLevelFile;
    protected $travelAgencyTypeFile;
    protected $nationalityFile;
    protected $orderSourceFile;

    public function __construct()
    {
        $this->cityTownFile = storage_path('misc/cityTown.json');
        $this->bankCodeFile = storage_path('misc/bankCode.json');
        $this->historicLevelFile = storage_path('misc/historicLevel.json');
        $this->travelAgencyTypeFile = storage_path('misc/travelAgencyType.json');
        $this->organizations = storage_path('misc/organizations.json');
        $this->nationalityFile = storage_path('misc/nationality.json');
        $this->orderSourceFile = storage_path('misc/orderSource.json');
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

    public function getTravelAgencyType()
    {
        $jsonString = file_get_contents($this->travelAgencyTypeFile);
        return json_decode($jsonString, true);
    }

    public function getOrganization()
    {
        $jsonString = file_get_contents($this->organizations);
        return json_decode($jsonString, true);
    }

    public function getNationality()
    {
        $jsonString = file_get_contents($this->nationalityFile);
        return json_decode($jsonString, true);
    }

    public function getOrderSource()
    {
        $jsonString = file_get_contents($this->orderSourceFile);
        return json_decode($jsonString, true);
    }
}
