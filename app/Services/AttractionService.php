<?php

namespace App\Services;

use App\Models\ComponentAttraction;

class AttractionService
{
    private $componentAttraction;

    public function __construct(ComponentAttraction $componentAttraction)
    {
        $this->componentAttraction = $componentAttraction;
    }

    public function create($validated)
    {
        try
        {
            return $this->componentAttraction->create($validated)->toArray();
        }
        catch (\Exception $e)
        {
            return ['error' => $e->getMessage()];
        }
        
    }

}