<?php

namespace App\Services;

class RequestCostService
{
    public function after_delete_component_cost($itinerary_group_cost, $delete_component){
        $delete_component_data['_id'] = $delete_component['itinerary_group_id'];
        if($delete_component['type'] === "attractions" || $delete_component['type'] === "activities"){
            $delete_component_data['itinerary_group_cost'] = $itinerary_group_cost - $delete_component['sum'];
            if($delete_component_data['itinerary_group_cost'] < 0){
                return response()->json(["扣除後的供應商該項目的 itinerary_group_cost 不可小於0"], 400);
            }

            //分全票半票
            for($i = 0; $i < count($delete_component["pricing_detail"]); $i++){
                if($delete_component['pricing_detail'][$i]['name'] === "全票"){
                    $delete_component_data["accounting.adult.cost"] = $delete_component['pricing_detail'][$i]['unit_price'];
                }
                elseif($delete_component['pricing_detail'][$i]['name'] === "半票"){
                    $delete_component_data['accounting.child.cost'] = $delete_component['pricing_detail'][$i]['unit_price'];
                }
            }
            if(!array_key_exists('accounting.adult.cost', $delete_component_data)){
                $delete_component_data['accounting.adult.cost'] = 0;
            }
            if(!array_key_exists('accounting.child.cost', $delete_component_data)){
                $delete_component_data['accounting.child.cost'] = 0;
            }
        }

        if($delete_component['type'] === "accomendations" || $delete_component['type'] === "restaurants" || $delete_component['type'] === "transportations"){
            $delete_component_data['itinerary_group_cost'] = $itinerary_group_cost - $delete_component['sum'];
            if($delete_component_data['itinerary_group_cost'] < 0){
                return response()->json(["扣除後的供應商該項目的 itinerary_group_cost 不可小於0"], 400);
            }
            $count_all = 0;
            $cost_all = 0;
            for($i = 0; $i < count($delete_component_data["pricing_detail"]); $i++){
                $count_all += $delete_component_data["pricing_detail"][$i]['count'];
                $cost_all += $delete_component_data["pricing_detail"][$i]['subtotal'];
            }
            $delete_component_data['accounting.child.cost'] = ceil($cost_all / $count_all);
            $delete_component_data['accounting.adult.cost'] = ceil($cost_all / $count_all);
        }

        if($delete_component['type'] === "guides"){
            $delete_component_data['itinerary_group_cost'] =  $itinerary_group_cost - $delete_component['subtotal'];
            if($delete_component_data['itinerary_group_cost'] < 0){
                return response()->json(["扣除後的供應商該項目的 itinerary_group_cost 不可小於0"], 400);
            }
            $delete_component_data['accounting.child.cost'] = ceil($delete_component["subtotal"] / $delete_component['total_people']);
            $delete_component_data['accounting.adult.cost'] = ceil($delete_component["subtotal"] / $delete_component['total_people']);
        }
        return $delete_component_data;
    }
}
?>
