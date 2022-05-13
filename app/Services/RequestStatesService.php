<?php

namespace App\Services;

class RequestStatesService
{

    public function payment_status($validated, $itinerary_group_past_data){
        if(array_key_exists("payment_status", $validated)){
            if($validated['payment_status'] !== $itinerary_group_past_data['payment_status']){
                if($itinerary_group_past_data["payment_status"] === '未付款'){
                    if($validated['payment_status'] === '已棄單，待退款' || $validated['payment_status'] === '已棄單，已退款'){
                        return response()->json(['error' => "過去[付款狀態]為[未付款]，因此[付款狀態]只可改到[未付款]、[已付訂金]、[已付全額]、[已棄單，待退款]、[已棄單，免退款]"], 400);
                    }
                }
                else if($itinerary_group_past_data["payment_status"] === '已付訂金'){
                    if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "過去[付款狀態]為[已付訂金]，因此[付款狀態]只可改到[已付訂金]、[已付全額]、[已棄單，待退款]"], 400);
                    }
                }
                else if($itinerary_group_past_data["payment_status"] === '已付全額'){
                    if($validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "過去[付款狀態]為[已付全額]，因此[付款狀態]只可改到[已付全額]、[已棄單，待退款]"], 400);
                    }
                }
                else if($itinerary_group_past_data["payment_status"] === '已棄單，待退款'){
                    if($validated['payment_status'] !== "已棄單，已退款"){
                        return response()->json(['error' => "過去[付款狀態]為[已棄單，待退款]，因此[付款狀態]只可改到[已棄單，待退款]、[已棄單，已退款]"], 400);
                    }
                }
            }

            if(array_key_exists('payment_status', $validated) && array_key_exists('booking_status', $validated)){
                if($validated['booking_status'] === '未預訂'){
                    if($validated['payment_status'] !== '未付款'){
                        return response()->json(['error' => "預定狀態為[未預訂]時，付款狀態只可為[未付款]。"], 400);
                    }
                }
                else if($validated['booking_status'] === '已預訂'){
                    if($validated['payment_status'] === '已棄單，待退款'){
                        return response()->json(['error' => "預定狀態為[已預訂]時，付款狀態不可為[已棄單，待退款]。"], 400);
                    }
                    else if($validated['payment_status'] === '已棄單，已退款'){
                        return response()->json(['error' => "預定狀態為[已預訂]時，付款狀態不可為[已棄單，已退款]。"], 400);
                    }
                    else if($validated['payment_status'] === '已棄單，免退款'){
                        return response()->json(['error' => "預定狀態為[已預訂]時，付款狀態不可為[已棄單，免退款]。"], 400);
                    }
                }
                else if($validated['booking_status'] === '待退訂'){
                    if($validated['payment_status'] !== '已棄單，待退款' && $validated['payment_status'] !== '已棄單，免退款'){
                        return response()->json(['error' => "預定狀態為[待退訂]，付款狀態只可[已棄單，待退款]或[已棄單，免退款]。"], 400);
                    }
                }
                else if($validated['booking_status'] === '已退訂'){
                    if($validated['payment_status'] !== '已棄單，已退款' && $validated['payment_status'] !== '已棄單，免退款'){
                        return response()->json(['error' => "預定狀態必須為[已退訂]，付款狀態只可是[已棄單，已退款]。"], 400);
                    }
                }
                else{
                    return response()->json(['error' => "預訂狀態可能輸入錯誤!"], 400);
                }
            }

            return true;
        }
        else{
            return response()->json(['error' => "沒有付款狀態"]);
        }
    }

    public function booking_status($validated, $itinerary_group_past_data){
        if($itinerary_group_past_data === null){
            return response()->json(['error' => "這筆資料可能已經被刪除嘞!"]);
        }

        if(array_key_exists("booking_status", $validated)){
            if($validated['booking_status'] !== $itinerary_group_past_data['booking_status']){
                if($itinerary_group_past_data["booking_status"] === "未預訂"){
                    if($validated['booking_status'] !== "已預訂"){
                        return response()->json(['error' => "之前[預定狀態]為[未預定]，因此[預定狀態]只可維持或改到[已預訂]"], 400);
                    }
                }
                else if($itinerary_group_past_data["booking_status"] === "已預訂"){
                    if($validated['booking_status'] !== "待退訂"){
                        return response()->json(['error' => "之前[預定狀態]為[已預訂]，因此[預定狀態]只可維持或改到[待退訂]"], 400);
                    }
                }
                else if($itinerary_group_past_data["booking_status"] === "待退訂"){
                    if($validated['booking_status'] !== "已退訂"){
                        return response()->json(['error' => "之前[預定狀態]為[待退訂]，因此[預定狀態]只可維持或改到[已退訂]"], 400);
                    }
                }
                else{
                    return response()->json(['error' => "預定狀態可能輸入錯誤!"], 400);
                }
            }
            return true;
        }
        else{
            return response()->json(['error' => "沒有預定狀態。"]);
        }

    }

    public function change_search_sort($filter_sort){
        if($filter_sort == "travelStart_1"){ //實際行程時間由舊到新
            $filter['sort']["travel_start"] = 1;
        }
        else if($filter_sort === "travelStart_-1"){ //實際行程時間由新到舊
            $filter['sort']["travel_start"] = -1;
        }
        else if($filter_sort === "totalPeople_1"){ //參團人數由少到多
            $filter['sort']["total_people"] = 1;
        }
        else if($filter_sort === "totalPeople_-1"){ //參團人數由多到少
            $filter['sort']["total_people"] = -1;
        }
        $filter['sort']["created_at"] = -1;
        return $filter['sort'];
    }

    public function delete_data($to_deleted){
        // 要放入元件刪除資料庫
        $to_deleted['component_id'] = $to_deleted['_id'];
        $to_deleted['deleted_at'] = null;
        $to_deleted['deleted_reason'] = null;

        // 先刪除共同有的
        unset($to_deleted['sort']);
        unset($to_deleted['_id']);
        unset($to_deleted['created_at']);
        unset($to_deleted['updated_at']);

        // 按照分類刪除
        if($to_deleted['type'] == 'attractions') {
            unset($to_deleted['imgs']);
            unset($to_deleted['description']);
            unset($to_deleted['intro_summary']);
            unset($to_deleted['note']);
            unset($to_deleted['ticket']);
            unset($to_deleted['tel']);
            unset($to_deleted['address']);
            unset($to_deleted['address_town']);
            unset($to_deleted['address_city']);
            unset($to_deleted['private']);
        }
        else if($to_deleted['type'] == 'transportations') {
            unset($to_deleted['is_display']);
            unset($to_deleted['fee']);
            unset($to_deleted['intro']);
            unset($to_deleted['owned_by']);
            unset($to_deleted['imgs']);
            unset($to_deleted['company_tel']);
            unset($to_deleted['company_fax']);
            unset($to_deleted['experience']);
            unset($to_deleted['address']);
            unset($to_deleted['address_town']);
            unset($to_deleted['address_city']);
            unset($to_deleted['private']);
        }
        else if($to_deleted['type'] == 'guides') {
            unset($to_deleted['languages']);
            unset($to_deleted['imgs']);
            unset($to_deleted['gender']);
            unset($to_deleted['cell_phone']);
            unset($to_deleted['fee']);
            unset($to_deleted['tel']);
            unset($to_deleted['private']);
            unset($to_deleted['note']);
        }
        else if($to_deleted['type'] == 'activities') {
            unset($to_deleted['activity_items']);
            unset($to_deleted['imgs']);
            unset($to_deleted['intro_summary']);
            unset($to_deleted['description']);
            unset($to_deleted['attraction_name']);
            unset($to_deleted['tel']);
            unset($to_deleted['private']);
            unset($to_deleted['note']);
        }
        else if($to_deleted['type'] == 'accomendations') {
            unset($to_deleted['room']);
            unset($to_deleted['tel']);
            unset($to_deleted['address']);
            unset($to_deleted['address_town']);
            unset($to_deleted['address_city']);
            unset($to_deleted['intro_summary']);
            unset($to_deleted['description']);
            unset($to_deleted['imgs']);
            unset($to_deleted['private']);
            unset($to_deleted['note']);
        }
        else if($to_deleted['type'] == 'restaurants') {
            unset($to_deleted['meals']);
            unset($to_deleted['tel']);
            unset($to_deleted['address']);
            unset($to_deleted['address_town']);
            unset($to_deleted['address_city']);
            unset($to_deleted['intro_summary']);
            unset($to_deleted['description']);
            unset($to_deleted['imgs']);
            unset($to_deleted['private']);
            unset($to_deleted['note']);
        }


        return $to_deleted;
    }
}
?>
