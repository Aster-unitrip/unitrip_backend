<?php

namespace App\Services;
use Ds\Set;
use Ds\push;


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
                $reservation_data['attractions'][$i]["detail"]['date'] = $data['attractions'][$i]['date'];
                $reservation_data['attractions'][$i]["detail"]['sort'] = $data['attractions'][$i]['sort'];
                $reservation_data['attractions'][$i]["detail"]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
        }else{
            return array();
        }

        // 2. 體驗 參團編號_體驗名稱
        if(count($data['activities']) !== 0){
            for($i = 0; $i < count($data['activities']); $i++){
                $reservation_data['activities'][$i]['reservation_sort'] = $i+1;
                $reservation_data['activities'][$i]['reservation_name'] = $cus_group_code."_".$data['activities'][$i]['name'];
                $reservation_data['activities'][$i]["detail"]['date'] = $data['activities'][$i]['date'];
                $reservation_data['activities'][$i]["detail"]['sort'] = $data['activities'][$i]['sort'];
                $reservation_data['activities'][$i]["detail"]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
        }else{
            return array();
        }

        // 3. 交通工具 參團編號_車行名稱_車型名稱
        if(count($data['transportations']) !== 0){
            for($i = 0; $i < count($data['transportations']); $i++){
                $reservation_data['transportations'][$i]['reservation_sort'] = $i+1;
                $reservation_data['transportations'][$i]['reservation_name'] = $cus_group_code."_".$data['transportations'][$i]['transportation_rental_agency']."_".$data['transportations'][$i]['model'];
                $reservation_data['transportations'][$i]["detail"]['sort'] = $data['transportations'][$i]['sort'];
                $reservation_data['transportations'][$i]["detail"]['date_start'] = $data['transportations'][$i]['date_start'];
                $reservation_data['transportations'][$i]["detail"]['date_end'] = $data['transportations'][$i]['date_end'];
                $reservation_data['transportations'][$i]["detail"]['itinerary_group_id'] = $data['itinerary_group_id'];

            }
        }else{
            return array();
        }

        // 4. 導遊 參團編號_導遊_導遊姓名
        if(count($data['guides']) !== 0){
            for($i = 0; $i < count($data['guides']); $i++){
                $reservation_data['guides'][$i]['reservation_sort'] = $i+1;
                $reservation_data['guides'][$i]['reservation_name'] = $cus_group_code."_".$data['guides'][$i]['name'];
                $reservation_data['guides'][$i]["detail"]['sort'] = $data['guides'][$i]['sort'];
                $reservation_data['guides'][$i]["detail"]['date_start'] = $data['guides'][$i]['date_start'];
                $reservation_data['guides'][$i]["detail"]['date_end'] = $data['guides'][$i]['date_end'];
                $reservation_data['guides'][$i]["detail"]['itinerary_group_id'] = $data['itinerary_group_id'];
            }
        }else{
            return array();
        }

        // 5. 飯店(需要討論case) 參團編號_飯店名稱_日期
        // sort 用 array
        if(count($data['accomendations']) !== 0){
            $compare_before = array();
            for($i = 0; $i < count($data['accomendations']); $i++){
                $compare_before[$i] = $data['accomendations'][$i]['_id'];
            }
            $compare_before = array_unique($compare_before);

            for($i = 0; $i < count($data['accomendations']); $i++){
                for($j = 0; $j < count($compare_before); $j++){
                    if($i === array_keys($compare_before)[$j]){// 第一次
                        $reservation_data['accomendations'][$j]['reservation_sort'] = $i+1;
                        $reservation_data['accomendations'][$j]['reservation_name'] = $cus_group_code."_".$data['accomendations'][$j]['name']."_".$this->date_format($data['accomendations'][$i]['date']);
                        $s['sort'] = $data['accomendations'][$i]['sort'];
                        $s['date'] = $data['accomendations'][$i]['date'];
                        $s['itinerary_group_id'] = $data['itinerary_group_id'];
                        $reservation_data['accomendations'][$j]['detail'][] = $s;
                        //$reservation_data['accomendations'][$j]['component_id'] = $data['accomendations'][$i]['_id'];
                        break;
                    }
                    elseif($compare_before[$j] === $data['accomendations'][$i]['_id']){
                        $s['sort'] = $data['accomendations'][$i]['sort'];
                        $s['date'] = $data['accomendations'][$i]['date'];
                        $s['itinerary_group_id'] = $data['itinerary_group_id'];
                        $reservation_data['accomendations'][$j]['detail'][] = $s;
                        break;
                    }
                }
            }
        }else{
            return array();
        }

        // 6. 餐廳(需要討論case) 參團編號_餐廳名稱_日期
        // TODO US-647
        if(count($data['restaurants']) !== 0){
            $compare_before = array();
            for($i = 0; $i < count($data['restaurants']); $i++){
                $compare_before[$i] = $data['restaurants'][$i]['_id'];
            }
            $compare_before = array_unique($compare_before);

            for($i = 0; $i < count($data['restaurants']); $i++){
                for($j = 0; $j < count($compare_before); $j++){
                    if($i === array_keys($compare_before)[$j]){// 第一次
                        $reservation_data['restaurants'][$j]['reservation_sort'] = $i+1;
                        $reservation_data['restaurants'][$j]['reservation_name'] = $cus_group_code."_".$data['restaurants'][$j]['name']."_".$this->date_format($data['restaurants'][$i]['date']);
                        $s['sort'] = $data['restaurants'][$i]['sort'];
                        $s['date'] = $data['restaurants'][$i]['date'];
                        $s['itinerary_group_id'] = $data['itinerary_group_id'];
                        $reservation_data['restaurants'][$j]['detail'][] = $s;
                        break;
                    }
                    elseif($compare_before[$j] === $data['restaurants'][$i]['_id']){
                        $s['sort'] = $data['restaurants'][$i]['sort'];
                        $s['date'] = $data['restaurants'][$i]['date'];
                        $s['itinerary_group_id'] = $data['itinerary_group_id'];
                        $reservation_data['restaurants'][$j]['detail'][] = $s;
                        break;
                    }
                }
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


    public function date_format($date){
        $date_substr = substr($date, 0, 10);
        $date_after_format = preg_replace('/-/', "", $date_substr);
        return $date_after_format;
    }



}
?>
