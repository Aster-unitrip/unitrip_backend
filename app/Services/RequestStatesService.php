<?php

namespace App\Services;

class RequestStatesService
{
    public function payment_status($validated, $itinerary_group_past_data){
            //return $find_name."payment_status";
            if(array_key_exists("payment_status", $validated)){
                if($itinerary_group_past_data['payment_status'] !== $validated["payment_status"]){
                    // 都已符合預定狀態後的付款狀態改變
                    switch($itinerary_group_past_data["payment_status"]){
                        case "未付款"://0
                            if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                                return response()->json(['error' => "只可改到狀態1、2、5"], 400);
                            }
                            break;
                        case "已付訂金"://1
                            if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                                return response()->json(['error' => "只可改到狀態2、3"], 400);
                            }
                            break;
                        case "已付全額"://2
                            if($validated['payment_status'] !== "已棄單，待退款"){
                                return response()->json(['error' => "只可改到狀態3"], 400);
                            }else if($validated['booking_status'] === ""){

                            }
                            break;
                        case "已棄單，待退款"://3
                            if($validated['payment_status'] !== "已棄單，已退款"){
                                return response()->json(['error' => "只可改到狀態4"], 400);
                            }
                            break;
                    }
                    if(array_key_exists('payment_status', $validated) && array_key_exists('booking_status', $validated)){
                        if($validated['booking_status'] === '待退訂' && $validated['payment_status'] !== '已棄單，待退款'){
                            return response()->json(['error' => "付款狀態為[已棄單，待退款]時，預定狀態必須為[待退訂]。"], 400);
                        }
                        if($validated['booking_status'] === '已退訂' && ($validated['payment_status'] !== '已棄單，已退款' || $validated['payment_status']!== '已棄單，免退款')){
                            return response()->json(['error' => "付款狀態為[已棄單，已退款]或[已棄單，免退款]時，預定狀態必須為[已退訂]。"], 400);
                        }
                    }

                }
            }else{
                return response()->json(['error' => "沒有 payment_status"]);
            }
    }
    public function booking_status($validated, $itinerary_group_past_data){
        if(array_key_exists("booking_status", $validated) && $itinerary_group_past_data['payment_status'] !== $validated['payment_status']){
            switch($itinerary_group_past_data["booking_status"]){
                case "未預訂":
                    if($validated['booking_status'] !== "已預訂"){
                        return response()->json(['error' => "只可改到狀態已預訂"], 400);
                    }
                    break;
                case "已預訂":
                    if($validated['booking_status'] !== "待退訂"){
                        return response()->json(['error' => "只可改到狀態待退訂"], 400);
                    }
                    break;
                case "待確認退訂":
                    if($validated['booking_status'] !== "已退訂"){
                        return response()->json(['error' => "只可改到狀態已退訂"], 400);
                    }
                    break;
            }
        }
    }
}
?>
