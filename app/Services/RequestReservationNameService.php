<?php

namespace App\Services;

class RequestReservationNameService
{
    public function get_reservation_data($data){

        $cus_group_code = $data['cus_group_code'];

        // 先處理所需資料就好 剩下的之後拉

        // 1. 景點
        if(count($data['attractions']) !== 0){
            for($i = 0; $i < count($data['attractions']); $i++){
                $reservation_data['attractions'][$i]['reservation_sort'] = $i+1;
                $reservation_data['attractions'][$i]['reservation_name'] = $cus_group_code."_".$data['attractions'][$i]['name'];
                $reservation_data['attractions'][$i]['date'] = $data['attractions'][$i]['date'];
                $reservation_data['attractions'][$i]['sort'] = $data['attractions'][$i]['sort'];
                $reservation_data['attractions'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
        }else{
            return array();
        }

        // 2. 體驗 參團編號_體驗名稱
        if(count($data['activities']) !== 0){
            for($i = 0; $i < count($data['activities']); $i++){
                $reservation_data['activities'][$i]['reservation_sort'] = $i+1;
                $reservation_data['activities'][$i]['reservation_name'] = $cus_group_code."_".$data['activities'][$i]['name'];
                $reservation_data['activities'][$i]['date'] = $data['activities'][$i]['date'];
                $reservation_data['activities'][$i]['sort'] = $data['activities'][$i]['sort'];
                $reservation_data['activities'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
        }else{
            return array();
        }

        // 3. 交通工具 參團編號_車行名稱_車型名稱
        if(count($data['transportations']) !== 0){
            for($i = 0; $i < count($data['transportations']); $i++){
                $reservation_data['transportations'][$i]['reservation_sort'] = $i+1;
                $reservation_data['transportations'][$i]['reservation_name'] = $cus_group_code."_".$data['transportations'][$i]['transportation_rental_agency']."_".$data['transportations'][$i]['model'];
                $reservation_data['transportations'][$i]['sort'] = $data['transportations'][$i]['sort'];
                $reservation_data['transportations'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];

            }
        }else{
            return array();
        }

        // 4. 導遊 參團編號_導遊_導遊姓名
        if(count($data['guides']) !== 0){
            for($i = 0; $i < count($data['guides']); $i++){
                $reservation_data['guides'][$i]['reservation_sort'] = $i+1;
                $reservation_data['guides'][$i]['reservation_name'] = $cus_group_code."_".$data['guides'][$i]['name'];
                $reservation_data['guides'][$i]['sort'] = $data['guides'][$i]['sort'];
                $reservation_data['guides'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
        }else{
            return array();
        }

        // 5. 飯店(需要討論case) 參團編號_飯店名稱_日期
        // sort 用 array
        if(count($data['accomendations']) !== 0){
            // 用 id 作為區分依據
            for($i = 0; $i < count($data['accomendations']); $i++){
                $reservation_data['accomendations'][$i]['reservation_sort'] = $i+1;
                $reservation_data['accomendations'][$i]['reservation_name'] = $cus_group_code."_".$data['accomendations'][$i]['name'];
                $reservation_data['accomendations'][$i]['sort'] = $data['accomendations'][$i]['sort'];
                $reservation_data['accomendations'][$i]['component_id'] = $data['accomendations'][$i]['_id'];
                $reservation_data['accomendations'][$i]['date'] = $data['accomendations'][$i]['date'];
                $reservation_data['accomendations'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
            for($i = 0; $i < count($data['accomendations']); $i++){
                // 一樣刪掉
                if()
            }
        }else{
            return array();
        }


        // 6. 餐廳(需要討論case) 參團編號_餐廳名稱_日期
        // TODO US-647
        if(count($data['restaurants']) !== 0){
            // 用 id 作為區分依據
            for($i = 0; $i < count($data['restaurants']); $i++){
                $reservation_data['restaurants'][$i]['reservation_sort'] = $i+1;
                $reservation_data['restaurants'][$i]['reservation_name'] = $cus_group_code."_".$data['restaurants'][$i]['name'];
                $reservation_data['restaurants'][$i]['sort'] = $data['restaurants'][$i]['sort'];
                $reservation_data['restaurants'][$i]['component_id'] = $data['restaurants'][$i]['_id'];
                $reservation_data['restaurants'][$i]['date'] = $data['restaurants'][$i]['date'];
                $reservation_data['restaurants'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
            for($i = 0; $i < count($data['restaurants']); $i++){
                // 一樣刪掉
                if()
            }

        }else{
            return array();
        }



        return $reservation_data;
    }

    public function is_array_empty($component_object_each){
        if(count($component_object_each) !== 0){
            for($i = 0; $i < count($component_object); $i++){
                $component_object[$i]['reservation_name'] = $cus_group_code."_".$component_object[$i]['name'];
            }
            return $component_object;
        }else{
            return array();
        }
    }


    public function merge_reservation_name($component_object_each){
    }


}
?>
