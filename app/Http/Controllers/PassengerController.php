<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Rules\Boolean;


use Validator;

class PassengerController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService){
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->order_passenger_rule = [
            '_id' => 'string',
            'order_id' => 'required|string',
            'name' => 'required|string|max:30',
            'name_en' => 'string|max:30',
            'nationality' => 'required|string',
            'company' => 'string|max:50',
            'gender' => 'required|string|max:50',
            'id_number' => 'string|max:50',
            'passport_number' => 'string|max:50',
            'birthday' => 'required|string|date',
            'is_vegetarian' => ['nullable', new Boolean],
            'email' => 'email',
            'phone' => 'required|string',
            'job' => 'string|max:50',
            'needs' => 'string|max:500',
            'address' => 'array',
        ];
        $this->passenger_profile_rule = [
            '_id' => 'string',
            'name' => 'required|max:30',
            'name_en' => 'string|max:30',
            'nationality' => 'required|string',
            'company' => 'string|max:50',
            'gender' => 'required|string|max:50',
            'id_number' => 'string|max:50',
            'passport_number' => 'string|max:50',
            'birthday' => 'required|string|date',
            'is_vegetarian' => ['nullable', new Boolean],
            'email' => 'email',
            'phone' => 'required|string',
            'job' => 'string|max:50',
            'needs' => 'string|max:500',
            'address' => 'array',
            'note' => 'string|max:500',
            'mtp_number' => 'string|max:50',
            'visa_number' => 'string|max:50',
        ];
    }

    public function get_by_id($id){   //$id => passenger_profile_id

        // 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        $passenger_profile_data = $this->requestService->get_one('passenger_profile', $id);
        return $passenger_profile_data;

    }

    public function edit_passenger_profile(Request $request){

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->passenger_profile_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $validated['address']['detail'] = $validated['address']['city'].$validated['address']['town'].$validated['address']['address'];
        $validated = $this->ensure_value_is_upper('passenger_profile',$validated);
        $passenger_profile_data = $this->requestService->update_one('passenger_profile', $validated);
        return $passenger_profile_data;

    }

    public function get_by_order_passenger($id){//$id => 訂單id

        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        $order = $this->requestService->get_one('cus_orders', $id);
        $order_data = json_decode($order->getContent(), true);
        if($owned_by !== $order_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        //找到該訂單下所有使用者資訊
        $filter["order_id"] = $id;
        $passenger_data = $this->requestService->aggregate_search('passengers', null, $filter, $page=0);
        return $passenger_data;
    }

    public function edit_order_passenger(Request $request){

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->order_passenger_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        //array 中每一筆單的 order_id 都要是一樣的
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        $order = $this->requestService->get_one('cus_orders', $validated['order_id']);
        $order_data = json_decode($order->getContent(), true);
        if(array_key_exists('count', $order_data) && $order_data['count'] === 0){
            return response()->json(['error' => '沒有這筆訂單(no order_id)']);
        }
        if($owned_by !== $order_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // 修改資料
        $validated['address']['detail'] = $validated['address']['city'].$validated['address']['town'].$validated['address']['address'];
        $validated = $this->ensure_value_is_upper('order_passenger',$validated);

        // 取得CRM 中旅客id，修改資料
        $passenger_profile_id = $this->get_passenger_profile_id($validated);

        // 判斷新增或是修改
        if(array_key_exists("_id", $validated)){ //修改
            $passenger_data = $this -> ensure_passengers_key($validated, $passenger_profile_id);
            $result = $this->requestService->update_one('passengers', $passenger_data);
        }
        else if(!array_key_exists("_id", $validated)){ //新增
            // 補上passenger_profile 該旅客id，新增於 passengers 中
            $passenger_data = $this -> ensure_passengers_key($validated, $passenger_profile_id);
            $result = $this->requestService->insert_one('passengers', $passenger_data);
        }
        return $result;
    }

    public function passenger_profile_list(Request $request){

        // 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }


    }

    public function is_first_time_user($data){
        // 搜尋方式 : 搜尋該筆
        $filter['name'] = $this->ensure_name_key($data['name']);
        $filter['birthday'] = $data['birthday'];
        $searchResult = $this->requestService->aggregate_search("passenger_profile", null, $filter, $page=0);
        $searchResult = json_decode($searchResult->content(), true);
        if(array_key_exists("count", $searchResult) && $searchResult['count'] > 0){ // 如果是第一筆訂單 則存入CRM
            $result['status'] = false;
            $result['passenger_profile_id'] = $searchResult['docs'][0]['_id'];
        }else{ // 如果不是第一筆訂單 不理
            $result['status'] =  true;
        }
        return $result;
    }

    public function get_passenger_profile_id($data){
        $result = $this->is_first_time_user($data);
        if($result['status'] === true){ // 如果是第一筆訂單 則存入CRM 並抓出旅客id
            $data_add_to_passenger_profile = $this -> ensure_passenger_profile_key($data);
            $content = $this->requestService->insert_one('passenger_profile', $data_add_to_passenger_profile);
            $data_add_to_passenger_profile = json_decode($content->getContent(), true);
            return $data_add_to_passenger_profile['inserted_id'];
        }
        else if($result['status'] === false){ // 如果不是第一筆訂單 抓出旅客id
            return $result['passenger_profile_id'];
        }
    }

    public function ensure_passenger_profile_key($data){

        // 修改姓名
        $data['name'] = $this->ensure_name_key($data['name']);
        $data['note'] = ""; // 備註
        $data['mtp_number'] = ""; //台胞證
        $data['visa_number'] = ""; // 簽證號碼
        $data['owned_by'] = auth()->user()->company_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');
        unset($data['order_id']);
        unset($data['is_representative']);
        unset($data['_id']);

        return $data;
    }

    public function ensure_passengers_key($data, $inserted_id){
        $data['passenger_profile_id'] = $inserted_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        // 新增用
        if(!array_key_exists("is_representative", $data)){
            $data['is_representative'] = false;
        }
        if(!array_key_exists("owned_by", $data)){
            $data['owned_by'] = auth()->user()->company_id;
        }
        if(!array_key_exists("created_at", $data)){
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['passenger_profile_id'] = $inserted_id;
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $data;
    }

    // 姓名轉換(過渡期)
    public function ensure_name_key($name){

        if(gettype($name) === 'string') {
            $name_changed['first_name'] = $name;
            $name_changed['last_name'] = "";
        }

        return $name_changed;
    }

    public function ensure_value_is_upper($path, $value){ //將需要為大寫value轉成大寫
        // mtp_number visa_number id_number passport_number
        if($path === "order_passenger"){
            $value['id_number'] = strtoupper($value['id_number']);
        }
        else if($path === "passenger_profile"){
            $value['mtp_number'] = strtoupper($value['mtp_number']);
            $value['visa_number'] = strtoupper($value['visa_number']);
            $value['id_number'] = strtoupper($value['id_number']);
        }

        // foreach($value as $key => $val) {
        //     if($key === 'mtp_number' || $key === 'visa_number' || $key === 'id_number' || $key === 'passport_number'){
        //         $val = strtoupper($val);
        //     }
        // }
        return $value;
    }

}
