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
                $reservation_data['attractions'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
                $reservation_data['attractions'][$i]['order_id'] = $data['order_id'];
                $reservation_data['attractions'][$i]["detail"]['date'] = $data['attractions'][$i]['date'];
                $reservation_data['attractions'][$i]["detail"]['sort'] = $data['attractions'][$i]['sort'];

            }
        }else{
            $reservation_data['attractions'] = array();
        }

        // 2. 體驗 參團編號_體驗名稱
        if(count($data['activities']) !== 0){
            for($i = 0; $i < count($data['activities']); $i++){
                $reservation_data['activities'][$i]['reservation_sort'] = $i+1;
                $reservation_data['activities'][$i]['reservation_name'] = $cus_group_code."_".$data['activities'][$i]['name'];
                $reservation_data['activities'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
                $reservation_data['activities'][$i]['order_id'] = $data['order_id'];
                $reservation_data['activities'][$i]["detail"]['date'] = $data['activities'][$i]['date'];
                $reservation_data['activities'][$i]["detail"]['sort'] = $data['activities'][$i]['sort'];
            }
        }else{
            $reservation_data['activities'] = array();

        }

        // 3. 交通工具 參團編號_車行名稱_車型名稱
        if(count($data['transportations']) !== 0){
            for($i = 0; $i < count($data['transportations']); $i++){
                $reservation_data['transportations'][$i]['reservation_sort'] = $i+1;
                $reservation_data['transportations'][$i]['reservation_name'] = $cus_group_code."_".$data['transportations'][$i]['transportation_rental_agency']."_".$data['transportations'][$i]['model'];
                $reservation_data['transportations'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
                $reservation_data['transportations'][$i]['order_id'] = $data['order_id'];
                $reservation_data['transportations'][$i]["detail"]['sort'] = $data['transportations'][$i]['sort'];
                $reservation_data['transportations'][$i]["detail"]['date_start'] = $data['transportations'][$i]['date_start'];
                $reservation_data['transportations'][$i]["detail"]['date_end'] = $data['transportations'][$i]['date_end'];
            }
        }else{
            $reservation_data['transportations'] = array();

        }

        // 4. 導遊 參團編號_導遊_導遊姓名
        if(count($data['guides']) !== 0){
            for($i = 0; $i < count($data['guides']); $i++){
                $reservation_data['guides'][$i]['reservation_sort'] = $i+1;
                $reservation_data['guides'][$i]['reservation_name'] = $cus_group_code."_".$data['guides'][$i]['name'];
                $reservation_data['guides'][$i]['itinerary_group_id'] = $data['itinerary_group_id'];
                $reservation_data['guides'][$i]['order_id'] = $data['order_id'];
                $reservation_data['guides'][$i]["detail"]['sort'] = $data['guides'][$i]['sort'];
                $reservation_data['guides'][$i]["detail"]['date_start'] = $data['guides'][$i]['date_start'];
                $reservation_data['guides'][$i]["detail"]['date_end'] = $data['guides'][$i]['date_end'];
            }
        }else{
            $reservation_data['guides'] = array();
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
                        $reservation_data['accomendations'][$j]['reservation_sort'] = $j+1;
                        $reservation_data['accomendations'][$j]['reservation_name'] = $cus_group_code."_".$data['accomendations'][$j]['name']."_".$this->date_format($data['accomendations'][$i]['date']);
                        $reservation_data['accomendations'][$j]['itinerary_group_id'] = $data['itinerary_group_id'];
                        $reservation_data['accomendations'][$j]['order_id'] = $data['order_id'];
                        $s['sort'] = $data['accomendations'][$i]['sort'];
                        $s['date'] = $data['accomendations'][$i]['date'];
                        $reservation_data['accomendations'][$j]['detail'][] = $s;
                        //$reservation_data['accomendations'][$j]['component_id'] = $data['accomendations'][$i]['_id'];
                        break;
                    }
                    else if($compare_before[$j] === $data['accomendations'][$i]['_id']){
                        $s['sort'] = $data['accomendations'][$i]['sort'];
                        $s['date'] = $data['accomendations'][$i]['date'];
                        $reservation_data['accomendations'][$j]['detail'][] = $s;
                        break;
                    }
                }
            }
        }else{
            $reservation_data['accomendations'] = array();
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
                        $reservation_data['restaurants'][$j]['reservation_sort'] = $j+1;
                        $reservation_data['restaurants'][$j]['reservation_name'] = $cus_group_code."_".$data['restaurants'][$j]['name']."_".$this->date_format($data['restaurants'][$i]['date']);
                        $reservation_data['restaurants'][$j]['itinerary_group_id'] = $data['itinerary_group_id'];
                        $reservation_data['restaurants'][$j]['order_id'] = $data['order_id'];
                        $s['sort'] = $data['restaurants'][$i]['sort'];
                        $s['date'] = $data['restaurants'][$i]['date'];
                        $reservation_data['restaurants'][$j]['detail'][] = $s;
                        break;
                    }
                    elseif($compare_before[$j] === $data['restaurants'][$i]['_id']){
                        $s['sort'] = $data['restaurants'][$i]['sort'];
                        $s['date'] = $data['restaurants'][$i]['date'];
                        $reservation_data['restaurants'][$j]['detail'][] = $s;
                        break;
                    }
                }
            }
        }else{
            $reservation_data['restaurants'] = array();
        }
        return $reservation_data;
    }

    public function is_array_empty($component_object, $component_name){
        if(array_key_exists($component_name, $component_object) && count($component_object) > 0){
            return $component_object[$component_name];
        }else{
            return array();
        }
    }

    public function date_format($date){
        $date_substr = substr($date, 0, 10);
        $date_after_format = preg_replace('/-/', "", $date_substr);
        return $date_after_format;
    }

<<<<<<< HEAD
    public function get_travel_agency($company_data){
       // 旅行社名稱 旅行社地址 旅行社聯絡人 訂單聯絡人分機 旅行社統編 旅行社電話 旅行社傳真

        $travel_agency['name'] = $company_data['title']; //旅行社名稱
        $travel_agency['address'] = $company_data['address_city'].$company_data['address_town'].$company_data['address']; //旅行社地址
        $travel_agency['contact_name'] = auth()->user()->contact_name; //旅行社聯絡人
        $travel_agency['contact_phone_extension'] = auth()->user()->contact_tel; //訂單聯絡人分機
=======
    public function get_travel_agency($data){
        $user_data = $data['user'];
        $company_data = $data['company'];

        $travel_agency['company_title'] = $company_data['title']; //旅行社名稱
        $travel_agency['company_address'] = $company_data['address_city'].$company_data['address_town'].$company_data['address']; //旅行社地址
        $travel_agency['contact_name'] = $user_data['contact_name']; //旅行社聯絡人
        $travel_agency['contact_phone_extension'] = $user_data['contact_tel']; //訂單聯絡人分機
        $travel_agency['email'] = $user_data['email']; //訂單聯絡人email
>>>>>>> daily
        $travel_agency['tax_id '] = $company_data['tax_id']; //旅行社統編
        $travel_agency['contact_tel'] = $company_data['tel']; //旅行社電話
        $travel_agency['fax'] = $company_data['fax']; //旅行社傳真
        return $travel_agency;

    }

    public function get_itinerary_group_guides($data){
        // 導遊 導遊聯繫方式
        for($i = 0; $i < count($data['guides']); $i++){
            $guide_data[$i]['name'] = $data['guides'][$i]['name'];
            $guide_data[$i]['cell_phone'] = $data['guides'][$i]['cell_phone'];
        }
        return $guide_data;
    }

    public function get_order_data($data){ //訂單相關資料 團號 訂房代表人 各人數 旅客國籍
        $reservation_order_data['adult_number'] = $data['adult_number'];
        $reservation_order_data['child_number'] = $data['child_number'];
        $reservation_order_data['baby_number'] = $data['baby_number'];
        $reservation_order_data['cus_group_code'] = $data['cus_group_code'];
        $reservation_order_data['representative_name'] = $data['representative']; //訂房代表人
        $reservation_order_data['representative_nationality'] = $data['nationality']; // 訂房代表人國籍
        $reservation_order_data['representative_phone'] = $data['phone']; //訂房代表人電話
        $reservation_order_data['representative_currency'] = $data['currency']; //訂房代表人使用錢幣
        return $reservation_order_data;
    }
}
?>
