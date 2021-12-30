<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;

class Guide
{
    public $subtotal;

    public function __construct($raw_data)
    {
        
        $this->component_id = $raw_data['component_id'];
        $this->type = $raw_data['type'];
        $this->unit_price = $raw_data['unit_price'];
        if ($raw_data['days'] <= 0) {
            throw new DataIncorrectException('days must be greater than 0');
        }
        elseif ($raw_data['days'] > 0) {
            $this->days = $raw_data['days'];
        }
        $this->subtotal = $raw_data['subtotal'];
        $this->check_subtotal();
    }

    private function check_unit_price()
    {
        // Check unit_price is right
    }

    private function check_subtotal()
    {
        if ($this->subtotal <= 0) {
            throw new DataIncorrectException('subtotal must be greater than 0');
        }
        elseif ($this->unit_price * $this->days != $this->subtotal) {
            throw new DataIncorrectException('subtotal must be equal to unit_price * days');
        }
        return true;
    }

    public function get_subtotal()
    {
        return $this->subtotal;
    }


}