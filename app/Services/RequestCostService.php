<?php

namespace App\Services;

class RequestCostService
{
    public function after_delete_component_cost($delete_component){
        //針對不同元件抓出刪除成本
        /*
        需要資料
        $delete_component['type']
        $delete_component['subtotal']
        $delete_component['sum']
        */
        // $delete_component['pricing_detail']

        if($delete_component['type'] === "attraction" || $delete_component['type'] === "activity" || $delete_component['type'] === "accomendation" || $delete_component['type'] === "restaurant" || $delete_component['type'] === "transportation"){
            $delete_component_cost = $delete_component['sum'];
        }
        if($delete_component['type'] === "guide"){
            $delete_component_cost = $delete_component['subtotal'];
        }
        return $delete_component_cost;
    }
}
?>
