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
use Exception;

class ItineraryGroupService
{

    public function __construct($raw_data){
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


        $guide_sum = 0;
        foreach ($raw_data['guides'] as $guide) {
            $guide = new Guide($guide);
            $guide_sum += $guide->subtotal;
        }
        $this->guides_cost_per_person = $guide_sum / $this->people_threshold;
        $this->calculate_adult_cost += $this->guides_cost_per_person;
        $this->calculate_child_cost += $this->guides_cost_per_person;
        // dump("guide: +".$this->guides_cost_per_person);

        $transportation_sum = 0;
        foreach ($raw_data['transportations'] as $transportation) {
            $transportation = new Transportation($transportation);
            $transportation_sum += $transportation->subtotal/$this->people_threshold;
        }
        $this->calculate_adult_cost += $transportation_sum;
        $this->calculate_child_cost += $transportation_sum;
        // dump("transportation: +".$transportation_sum);

        $misc_sum = 0;
        foreach($raw_data['misc'] as $m){
            $misc_sum += $m['subtotal'] / $this->people_threshold;

        }
        $this->calculate_adult_cost += $misc_sum;
        $this->calculate_child_cost += $misc_sum;
        // dump("misc: +".$misc_sum);

        $this->check_itinerary_components();


        // 不驗算最後售價了
        // $accounting = new Accounting($raw_data['accounting']);

        // dump("cal_adult_cost: ".$this->calculate_adult_cost);
        // dump('adult_cost: '.$this->adult_cost);
        // dump("cal_child_cost: ".$this->calculate_child_cost);
        // dump('child_cost: '.$this->child_cost);
        // if ($this->calculate_adult_cost != $this->adult_cost){
        //     // throw new WrongTypeException('Adult cost is not correct');
        //     throw new Exception("Adult cost is not correct");
        // }
        // elseif ($this->calculate_child_cost != $this->child_cost){
        //     // throw new WrongTypeException('Child cost is not correct');
        //     throw new Exception("Child cost is not correct");
        // }

    }

    private function check_itinerary_components(){
        foreach ($this->itinerary_content as $day) {
            foreach($day['components'] as $component) {
                if ($component['type'] == 'attraction'){
                    $attraction = new Attraction($component, $this->people_threshold);
                    $this->calculate_adult_cost += $attraction->get_adult_cost();
                    $this->calculate_child_cost += $attraction->get_child_cost();
                    // dump('attraction: +'.$attraction->get_adult_cost());
                    // dump($this->calculate_adult_cost);
                    // dump($this->calculate_child_cost);
                }
                elseif ($component['type'] == 'activity'){
                    $activity = new Activity($component, $this->people_threshold);
                    $this->calculate_adult_cost += $activity->get_cost_per_person();
                    $this->calculate_child_cost += $activity->get_cost_per_person();
                    // dump('activity: +'.$activity->get_cost_per_person());
                }
                elseif ($component['type'] == 'accomendation'){
                    $accomendation = new Accomendation($component, $this->people_threshold);
                    $this->calculate_adult_cost += $accomendation->get_cost_per_person();
                    $this->calculate_child_cost += $accomendation->get_cost_per_person();
                    // dump('accomendation: +'.$accomendation->get_cost_per_person());
                }
                elseif ($component['type'] == 'restaurant'){
                    $restaurant = new Restaurant($component, $this->people_threshold);
                    $this->calculate_adult_cost += $restaurant->get_cost_per_person();
                    $this->calculate_child_cost += $restaurant->get_cost_per_person();
                    // dump('restaurant: +'.$restaurant->get_cost_per_person());
                }
                elseif ($component['type'] == 'travel'){
                }
                else {
                    throw new WrongTypeException('Wrong component type. Please check unitrip definition');
                }
            }
        }
    }

    public function change_search_sort($filter_sort){

        if($filter_sort == "travelStart_1"){ //預計行程時間由舊到新
            $filter['sort']["travel_start"] = 1;
            $filter['sort']["created_at"] = -1;
        }
        else if($filter_sort === "travelStart_-1"){ //預計行程時間由新到舊
            $filter['sort']["travel_start"] = -1;
            $filter['sort']["created_at"] = -1;
        }
        else if($filter_sort === "createdAt_1"){ //建單日期由舊到新
            $filter['sort']["created_at"] = 1;
        }
        else if($filter_sort === "createdAt_-1"){ //建單日期由新到舊
            $filter['sort']["created_at"] = -1;
        }
        return $filter['sort'];
    }
}
