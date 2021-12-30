<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;

class Accomendation
{
    public $subtotal;

    public function __construct($raw_data)
    {
        $this->component_id = $raw_data['component_id'];
        $this->type = $raw_data['type'];
        $this->unit_price = $raw_data['unit_price'];
        $this->subtotal = $raw_data['subtotal'];

    }
}