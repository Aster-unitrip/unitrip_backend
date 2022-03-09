<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;

class Transportation extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        $this->_id = $raw_data['_id'];
        $this->type = $raw_data['type'];
        $this->days = $raw_data['pricing_detail'][0]['days'];
        $this->count = $raw_data['pricing_detail'][0]['count'];
        $this->unit_price = $raw_data['pricing_detail'][0]['unit_price'];
        $this->subtotal = $raw_data['pricing_detail'][0]['subtotal'];
        $this->check_subtotal();
    }

    private function check_subtotal()
    {
        if ($this->subtotal <= 0) {
            throw new DataIncorrectException('subtotal must be greater than 0');
        }
        elseif ($this->unit_price * $this->count != $this->subtotal) {
            throw new DataIncorrectException('subtotal must be equal to unit_price * days');
        }
        return true;
    }

}
