<?php

namespace App\Services;

use App\Exceptions\WrongTypeException;
use App\Services\Attraction;
use App\Services\Activity;
use App\Services\Accomendation;
use App\Services\Restaurant;
use App\Services\Itinerary;
use App\Services\Guide;
use App\Services\Transportation;


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

        foreach ($raw_data['guides'] as $guide) {
            $this->guides[] = new Guide($guide);
        }

        foreach ($raw_data['transportations'] as $transportation) {
            $this->transportations[] = new Transportation($transportation);
        }
        $this->misc = $raw_data['misc'];
        $this->accounting = $raw_data['accounting'];

        $this->average_guides_cost();
        $this->average_transportation_cost();

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

                }
                elseif ($component['type'] == 'activity'){

                }
                elseif ($component['type'] == 'accomendation'){

                }
                elseif ($component['type'] == 'restaurant'){

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
