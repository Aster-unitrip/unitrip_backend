<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;

class Transportation extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        $this->_id = $raw_data['_id'];
        $this->type = $raw_data['type'];
        $this->unit_price = $raw_data['unit_price'];
        if ($raw_data['days'] <= 0) {
            throw new DataIncorrectException('days must be greater than 0');
        }
        elseif( $raw_data['days'] > 0 ) {
            $this->days = $raw_data['days'];
        }
        if ($raw_data['count'] <= 0) {
            throw new DataIncorrectException('count must be greater than 0');
        }
        else {
            $this->count = $raw_data['count'];
        }
        $this->subtotal = $raw_data['subtotal'];
        $this->check_subtotal();
        $this->calculate_cost();
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
    private function calculate_cost()
    {
        // 沒有除以成團人數
        $this->cost_per_person = $this->unit_price * $this->days;
    }

    public function get_cost_per_person()
    {
        return $this->cost_per_person;
    }

}