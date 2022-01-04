<?php

use App\Exceptions\DataIncorrectException;

namespace App\Services;

class ComponentNode
{
    private $subtotal;
    private $adult_cost;
    private $child_cost;

    private function __construct($raw_data)
    {
        $this->raw_data = $raw_data;
        $this->component_id = $raw_data['component_id'];
        $this->type = $raw_data['type'];
        $this->pricing_detail = $raw_data['pricing_detail'];
        $this->subtotal = $raw_data['subtotal'];
        $this->check_subtotal();
    }

    private function check_subtotal()
    {
        $pricing_check = 0;
        foreach ($this->pricing_detail as $pricing) {
            $pricing_check += $pricing['sum'];
        }
        if ($this->subtotal != $pricing_check){
            throw new DataIncorrectException('Calculation not right.');
        }
    }


}

class Attraction extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        parent::__construct($raw_data);

    }
}

class Accomendation extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        parent::__construct($raw_data);
    }
}
