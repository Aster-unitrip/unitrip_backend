<?php

namespace App\Services;

class RequestStatesService
{
    public function payment_status($validated, $itinerary_group_past_data){
        if(array_key_exists("payment_status", $validated)){
            if($validated['payment_status'] !== $itinerary_group_past_data['payment_status']){
                if($itinerary_group_past_data["payment_status"] === '未付款'){
                    if($validated['payment_status'] === '已棄單，待退款' || $validated['payment_status'] === '已棄單，已退款'){
                        return response()->json(['error' => "付款狀態只可改到狀態[未付款]、[已付訂金]、[已付全額]、[已棄單，待退款]、[已棄單，免退款]"], 400);
                    }
                }
                if($itinerary_group_past_data["payment_status"] === '已付訂金'){
                    if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "付款狀態只可改到狀態[已付訂金]、[已付全額]、[已棄單，待退款]"], 400);
                    }
                }
                if($itinerary_group_past_data["payment_status"] === '已付全額'){
                    if($validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "付款狀態只可改到狀態[已付全額]、[已棄單，待退款]"], 400);
                    }
                }
                if($itinerary_group_past_data["payment_status"] === '已棄單，待退款'){
                    if($validated['payment_status'] !== "已棄單，已退款"){
                        return response()->json(['error' => "付款狀態只可改到狀態[已棄單，待退款]、[已棄單，已退款]"], 400);
                    }
                }
            }

            if(array_key_exists('payment_status', $validated) && array_key_exists('booking_status', $validated)){
                return $itinerary_group_past_data;
                if($validated['booking_status'] === '未預定'){
                    if($validated['payment_status'] !== '未付款'){
                        return response()->json(['error' => "預定狀態為[已預訂]時，付款狀態只可為[未付款]。"], 400);
                    }
                }
                if($validated['booking_status'] === '已預訂'){
                    if($validated['payment_status'] === '已棄單，待退款'){
                        return response()->json(['error' => "預定狀態必[已預訂]時，付款狀態不可為[已棄單，待退款]。"], 400);
                    }
                    if($validated['payment_status'] === '已棄單，已退款'){
                        return response()->json(['error' => "預定狀態為[已預訂]時，付款狀態不可為[已棄單，已退款]。"], 400);
                    }
                }
                if($validated['booking_status'] === '待退訂'){
                    if($validated['payment_status'] !== '已棄單，待退款'){
                        return response()->json(['error' => "預定狀態為[待退訂]，付款狀態只可[已棄單，待退款]。"], 400);
                    }
                }
                if($validated['booking_status'] === '已退訂'){
                    if($validated['payment_status'] !== '已棄單，已退款' && $validated['payment_status'] !== '已棄單，免退款'){
                        return response()->json(['error' => "付款狀態為[已棄單，已退款]或[已棄單，免退款]時，預定狀態必須為[已退訂]。"], 400);
                    }
                }
            }
        }else{
            return response()->json(['error' => "沒有付款狀態"]);
        }
    }

    //未預定 已預定 待退訂 已退定
    public function booking_status($validated, $itinerary_group_past_data){
        //return $itinerary_group_past_data;
        //return $validated;

        if(array_key_exists("booking_status", $validated)){
            return "111";
            if($validated['booking_status'] !== $itinerary_group_past_data['booking_status']){
                if($itinerary_group_past_data["booking_status"] === "未預訂"){
                    if($validated['booking_status'] !== "已預訂"){
                        return response()->json(['error' => "預定狀態只可改到狀態[已預訂]"], 400);
                    }
                }
                if($itinerary_group_past_data["booking_status"] === "已預訂"){
                    if($validated['booking_status'] !== "待退訂"){
                        return response()->json(['error' => "預定狀態只可改到狀態[待退訂]"], 400);
                    }
                }
                if($itinerary_group_past_data["booking_status"] === "待退訂"){
                    if($validated['booking_status'] !== "已退訂" || $validated['booking_status'] !== "待退訂"){
                        return response()->json(['error' => "預定狀態只可維持或改到狀態[已退訂]"], 400);
                    }
                }
            }
        }else{
            return response()->json(['error' => "沒有預定狀態"]);
        }
    }
}
?>