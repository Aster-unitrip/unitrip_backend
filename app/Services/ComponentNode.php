<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;

class ComponentNode
{
    private $subtotal;
    private $adult_cost;
    private $child_cost;

    public function __construct($raw_data)
    {
        $this->raw_data = $raw_data;
        $this->_id = $raw_data['_id'];
        $this->type = $raw_data['type'];
        $this->pricing_detail = $raw_data['pricing_detail'];
        $this->subtotal = $raw_data['subtotal'];
        $this->check_subtotal();
    }

    private function check_subtotal()
    {
        $pricing_check = 0;
        foreach ($this->pricing_detail as $pricing) {
            if ($pricing_check['sum'] != $pricing_check['unit_price'] * $pricing_check['count']){
                throw new DataIncorrectException('Calculation not right.');    
            }
            $pricing_check += $pricing['sum'];
        }
        if ($this->subtotal != $pricing_check){
            throw new DataIncorrectException('Calculation not right.');
        }
    }

    public function get_adult_cost(){
        return $this->adult_cost;
    }
    public function get_child_cost(){
        return $this->child_cost;
    }
    public function get_subtotal(){
        return $this->subtotal;
    }
    public function get_unit_price(){
        return $this->get_unit_price;
    }
}

class Attraction extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        parent::__construct($raw_data);
        foreach ($this->pricing_detail as $pricing){
            if ($pricing['name'] == '全票'){
                $this->adult_cost += $pricing['unit_price'];
            }
            elseif ($pricing['name'] == '半票'){
                $this->child_cost += $pricing['unit_price'];
            }
        }

    }
}

class Activity extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        parent::__construct($raw_data);
        $this->unit_price = $this->pricing_detail[0]['unit_price'];
    }
}

class Accomendation extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        parent::__construct($raw_data);
        $this->calculate_cost();
    }
    private function calculate_cost()
    {
        $this->cost_per_person = $this->pricing_detail[0]['unit_price'] / $this->pricing_detail[0]['suitable_passenger_number'];
    }
    public function get_cost_per_person()
    {
        return $this->cost_per_person;
    }
}

class Restaurant extends ComponentNode
{
    public function __construct($raw_data=null)
    {
        parent::__construct($raw_data);
        $this->calculate_cost();
    }

    private function calculate_cost()
    {
        $this->cost_per_person = $this->pricing_detail[0]['unit_price'] / $this->pricing_detail[0]['supply_people'];
    }

    public function get_cost_per_person()
    {
        return $this->cost_per_person;
    }
}

// class Guide extends ComponentNode
// {
//     private $cost_per_person;

//     public function __construct($raw_data=null)
//     {
//         $this->_id = $raw_data['_id'];
//         $this->type = $raw_data['type'];
//         $this->unit_price = $raw_data['unit_price'];
//         if ($raw_data['days'] <= 0) {
//             throw new DataIncorrectException('days must be greater than 0');
//         }
//         elseif ($raw_data['days'] > 0) {
//             $this->days = $raw_data['days'];
//         }
//         $this->subtotal = $raw_data['subtotal'];
//         $this->check_subtotal();
//         $this->calculate_cost();
//     }
//     private function check_subtotal()
//     {
//         if ($this->subtotal <= 0) {
//             throw new DataIncorrectException('subtotal must be greater than 0');
//         }
//         elseif ($this->unit_price * $this->days != $this->subtotal) {
//             throw new DataIncorrectException('subtotal must be equal to unit_price * days');
//         }
//         return true;
//     }

//     private function calculate_cost()
//     {   
//         // 沒有除以成團人數
//         $this->cost_per_person = $this->unit_price * $this->days;
//     }

//     public function get_cost_per_person()
//     {
//         return $this->cost_per_person;
//     }
// }

// class Transportation extends ComponentNode
// {
//     public function __construct($raw_data=null)
//     {
//         $this->_id = $raw_data['_id'];
//         $this->type = $raw_data['type'];
//         $this->unit_price = $raw_data['unit_price'];
//         if ($raw_data['days'] <= 0) {
//             throw new DataIncorrectException('days must be greater than 0');
//         }
//         elseif( $raw_data['days'] > 0 ) {
//             $this->days = $raw_data['days'];
//         }
//         if ($raw_data['count'] <= 0) {
//             throw new DataIncorrectException('count must be greater than 0');
//         }
//         else {
//             $this->count = $raw_data['count'];
//         }
//         $this->subtotal = $raw_data['subtotal'];
//         $this->check_subtotal();
//         $this->calculate_cost();
//     }

//     private function check_subtotal()
//     {
//         if ($this->subtotal <= 0) {
//             throw new DataIncorrectException('subtotal must be greater than 0');
//         }
//         elseif ($this->unit_price * $this->count != $this->subtotal) {
//             throw new DataIncorrectException('subtotal must be equal to unit_price * days');
//         }
//         return true;
//     }
//     private function calculate_cost()
//     {
//         // 沒有除以成團人數
//         $this->cost_per_person = $this->unit_price * $this->days;
//     }

//     public function get_cost_per_person()
//     {
//         return $this->cost_per_person;
//     }

// }