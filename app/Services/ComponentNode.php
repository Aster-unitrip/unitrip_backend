<?php

namespace App\Services;

use App\Exceptions\DataIncorrectException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

class ComponentNode
{
    public $total;
    public $adult_cost;
    public $child_cost = null;
    public $get_unit_price;
    public $cost_per_person;

    public function __construct($raw_data, $people_threshold)
    {
        $this->raw_data = $raw_data;
        $this->_id = $raw_data['_id'];
        $this->type = $raw_data['type'];
        $this->pricing_detail = $raw_data['pricing_detail'];
        $this->people_threshold = $people_threshold;
        $this->subtotal();
        $this->cal_cost_per_person();
    }

    private function subtotal()
    {
        $pricing_check = 0;
        foreach ($this->pricing_detail as $pricing) {
            if ($pricing['subtotal'] != $pricing['unit_price'] * $pricing['count']){
                throw new Exception('Calculation not right. In '.$pricing['name']);
            }
            $pricing_check += $pricing['subtotal'];
        }
        $this->total = $pricing_check;
    }
    private function cal_cost_per_person()
    {
        $this->cost_per_person = $this->total / $this->people_threshold;
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
    public function get_cost_per_person(){
        return $this->cost_per_person;
    }
}

class Attraction extends ComponentNode
{
    public function __construct($raw_data=null, $people_threshold=null)
    {
        parent::__construct($raw_data, $people_threshold);
        foreach ($this->pricing_detail as $pricing){
            if ($pricing['name'] == '全票'){
                $this->adult_cost += $pricing['unit_price'];
            }
            elseif ($pricing['name'] == '半票'){
                $this->child_cost += $pricing['unit_price'];
            }
        }
        if ($this->child_cost == null){
            $this->child_cost = $this->adult_cost;
        }

    }
}

class Activity extends ComponentNode
{
    public function __construct($raw_data=null, $people_threshold=null)
    {
        parent::__construct($raw_data, $people_threshold);
    }
}

class Accomendation extends ComponentNode
{
    public function __construct($raw_data=null, $people_threshold=null)
    {
        parent::__construct($raw_data, $people_threshold);
        // $this->calculate_cost();
    }
    // private function calculate_cost()
    // {
    //     $this->cost_per_person = $this->pricing_detail[0]['unit_price'] / $this->pricing_detail[0]['suitable_passenger_number'];
    // }
    // public function get_cost_per_person()
    // {
    //     return $this->cost_per_person;
    // }
}

class Restaurant extends ComponentNode
{
    public function __construct($raw_data=null, $people_threshold=null)
    {
        parent::__construct($raw_data, $people_threshold);
        // $this->calculate_cost();
    }

    // private function calculate_cost()
    // {
    //     $this->cost_per_person = $this->pricing_detail[0]['unit_price'] / $this->pricing_detail[0]['supply_people'];
    // }

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


class Accounting
{
    public function __construct($raw_data)
    {
        $this->adult_cost = $raw_data['adult']['cost'];
        $this->estimate_price = $raw_data['adult']['estimation_price']['price'];
        $this->estimate_price_profit_percentage = $raw_data['adult']['estimation_price']['profit_percentage'];
        $this->estimate_percentage = $raw_data['adult']['estimation_percentage']['profit_percentage'];
        $this->estimate_percentage_price = $raw_data['adult']['estimation_percentage']['price'];

        $this->child_cost = $raw_data['child']['cost'];
        $this->child_estimate_price = $raw_data['child']['estimation_price']['price'];
        $this->child_estimate_price_profit_percentage = $raw_data['child']['estimation_price']['profit_percentage'];
        $this->child_estimate_percentage = $raw_data['child']['estimation_percentage']['profit_percentage'];
        $this->child_estimate_percentage_price = $raw_data['child']['estimation_percentage']['price'];

        $this->check_price($this->adult_cost, $this->estimate_price, $this->estimate_price_profit_percentage);
        $this->check_percentage($this->adult_cost, $this->estimate_percentage, $this->estimate_percentage_price);

        $this->check_price($this->child_cost, $this->child_estimate_price, $this->child_estimate_price_profit_percentage);
        $this->check_percentage($this->child_cost, $this->child_estimate_percentage, $this->child_estimate_percentage_price);

    }

    private static function check_price($cost, $price, $profit_percentage)
    {
        $percentage = (($price - $cost) / $cost)*100;
        // dump("profit_percentage: ".$profit_percentage);
        // dump("percentage: ".round($percentage, 2));
        if (round($percentage, 2) != $profit_percentage){
            throw new DataIncorrectException('profit_percentage is not correct');
        }
    }

    private static function check_percentage($cost, $percent, $final_price)
    {
        $price = $cost * ($percent/100+1);
        // dump("final_price: ".$final_price);
        // dump("price: ".$price);
        if (round($price, 2) != $final_price){
            // throw new DataIncorrectException('price is not correct');
            throw new Exception('price is not correct');
        }
    }
}

