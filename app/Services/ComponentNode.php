<?php

namespace App\Services;

class ComponentNode
{
    private $subtotal;

    private function __construct($raw_data)
    {
        $this->raw_data = $raw_data;
        $this->component_id = $raw_data['component_id'];
        $this->type = $raw_data['type'];
        $this->subtotal = $this->raw_data['subtotal'];
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

class Accomendation extends ComponentNode
{

}
