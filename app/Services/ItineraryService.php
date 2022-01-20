<?php

namespace App\Services;

use App\Exceptions\WrongTypeException;

// use App\Services\Attraction;
// use App\Services\Activity;
// use App\Services\Accomendation;
// use App\Services\Restaurant;
use App\Services\Guide;
use App\Services\Transportation;
use App\Services\ComponentNode;


class ItineraryService
{

    public function __construct($raw_data)
    {
        $this->raw_data = $raw_data;
        $this->total_day = $raw_data['total_day'];
        $this->areas = $raw_data['areas'];
        $this->people_threshold = $raw_data['people_threshold'];
        $this->itinerary_content = $raw_data['itinerary_content'];
        $this->people_full = $raw_data['people_full'];
        $this->sub_categories = $raw_data['sub_categories'];

        // 實際計算
        $this->calculate_adult_cost = 0;
        $this->calculate_child_cost = 0;

        // 回傳的計算結果
        $this->adult_cost = $raw_data['accounting']['adult']['cost'];
        $this->child_cost = $raw_data['accounting']['child']['cost'];


        foreach ($raw_data['guides'] as $guide) {
            $guide = new Guide($guide);
            $this->calculate_adult_cost += $guide->get_cost_per_person()/$this->people_threshold;
            $this->calculate_child_cost += $guide->get_cost_per_person()/$this->people_threshold;
        }

        foreach ($raw_data['transportations'] as $transportation) {
            $this->transportation = new Transportation($transportation);
            $cost = $this->transportation->get_cost_per_person()/$this->people_threshold;
            $this->calculate_adult_cost += $cost;
            $this->calculate_child_cost += $cost;
        }
        $this->misc = $raw_data['misc'];
        $this->accounting = $raw_data['accounting'];

        // $this->average_guides_cost();
        // $this->average_transportation_cost();
        // $this->check_itinerary_components();

    }

    // Sum every subtotal and divide by people_threshold
    private function average_guides_cost()
    {
        $sum_cost = 0;
        foreach ($this->guides as $guide) {
            $sum_cost += $guide->subtotal;
        }
        $this->guides_cost_per_person = $sum_cost / $this->people_threshold;
    }

    private function average_transportation_cost()
    {
        $sum_cost = 0;
        foreach ($this->transportations as $transportation) {
            $sum_cost += $transportation->subtotal;
        }
        $this->transportation_cost_per_person = $sum_cost / $this->people_threshold;
    }

    private function check_itinerary_components(){
        foreach ($this->itinerary_content as $day) {
            foreach($day['components'] as $component) {
                if ($component['type'] == 'attraction'){
                    $attraction = new Attraction($component);
                    $this->calculate_adult_cost += $attraction->get_adult_cost();
                    $this->calculate->child_cost += $attraction->get_child_cost();
                }
                elseif ($component['type'] == 'activity'){
                    $activity = new Activity($component);
                    $this->calculate_adult_cost += $activity->get_unit_price();
                    $this->calculate_child_cost += $activity->get_unit_price();
                }
                elseif ($component['type'] == 'accomendation'){
                    $accomendation = new Accomendation($component);
                    $this->calculate_adult_cost += $accomendation->get_cost_per_person();
                    $this->calculate_child_cost += $accomendation->get_cost_per_person();
                }
                elseif ($component['type'] == 'restaurant'){
                    $restaurant = new Restaurant($component);
                    $this->calculate_adult_cost += $restaurant->get_cost_per_person();
                    $this->calculate_child_cost += $restaurant->get_cost_per_person();
                }
                elseif ($component['type'] == 'travel'){
                }
                else {
                    throw new WrongTypeException('Wrong component type. Please check unitrip definition');
                }
            }
        }
    }
}
