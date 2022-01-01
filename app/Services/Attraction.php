<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;

class Attraction
{
    public $subtotal;

    public function __contruct($raw_data)
    {
        $this->raw_data = $raw_data;
        $this->pricing_detail = $raw_data['pricing_detail'];
        $this->subtotal = $raw_data['subtotal'];
        $this->check_subtotal();

    }

    private function check_subtotal(){
        if ($this->subtotal < 0){
            throw DataIncorrectException('Subtotal must greater than 0.');
        }

        $pricing_check = 0;
        foreach ($this->pricing_detail as $pricing) {
            $pricing_check += $pricing['sum'];
        }
        if ($this->subtotal != $pricing_check){
            throw DataIncorrectException('Calculation not right.');
        }
    }
}
