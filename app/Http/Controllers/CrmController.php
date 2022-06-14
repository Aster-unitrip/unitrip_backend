<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Rules\Boolean;


use Validator;

class CrmController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService){
        $this->middleware('auth');
        $this->requestService = $requestService;
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
        $this->past_orders_list_rule = [
            'passenger_profile_id' => 'required|string',
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
        $passenger_profile_data =  json_decode($passenger_profile_data->content(), true);
        if($passenger_profile_data['gender'] == 1){
            $passenger_profile_data['gender'] = "male";
        }
        else{
            $passenger_profile_data['gender'] = "female";
        }
        return $passenger_profile_data;

    }

    public function edit_profile(Request $request){

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
        // TODO: 待前端修改完刪除中文判斷
        $validated['gender'] = $this->gender_transition($validated['gender']);
        $validated = $this->ensure_value_is_upper($validated);

        $passenger_profile_data = $this->requestService->update_one('passenger_profile', $validated);
        return $passenger_profile_data;

    }

    // 旅客管理列表搜尋
    public function profile_list(Request $request){

        // 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        // 處理分頁
        $filter = json_decode($request->getContent(), true);
        if(array_key_exists('page', $filter)){
            $page = $filter['page'];
            unset($filter['page']);
            if ($page <= 0) {
                return response()->json(['error' => 'page must be greater than 0'], 400);
            }
            else{
                $page = $page - 1;
            }
        }
        else{
            $page = 0;
        }

        $filter = $this->ensure_value_is_upper($filter);
        // 模糊搜尋
        if(array_key_exists('name', $filter)){
            $filter['$or'] = array(
                    array('name.first_name' => array('$regex' => $filter['name'])),
                    array('name.last_name' => array('$regex' => $filter['name']))
                );
            unset($filter['name']);
        }
        // 搜尋順序
        $filter['searchSort'] = array('created_at' => -1);


        // 查詢
        $passengers_profile_list_data = $this->requestService->aggregate_search('passenger_profile', null, $filter, $page);
        return $passengers_profile_list_data;

    }

    // 過往旅客紀錄
    public function past_orders_list(Request $request){ // passenger_profile_id
        $data = json_decode($request->getContent(), true);
        if (array_key_exists('page', $data)) {
            $page = $data['page'];
            unset($data['page']);
            if ($page <= 0) {
                return response()->json(['error' => 'page must be greater than 0'], 400);
            }
            else{
                $page = $page - 1;
            }
        }
        else{
            $page = 0;
        }

        // 檢查 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 檢查 符合規則
        $validator = Validator::make($data, $this->past_orders_list_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 查詢
        $past_orders_list_data = $this->requestService->aggregate_facet('passengers_past_order_records', null, $validated, $page);
        return $past_orders_list_data;

    }

    public function ensure_value_is_upper($value){ //將需要為大寫value轉成大寫
        // mtp_number visa_number id_number passport_number
        if(array_key_exists("id_number",$value)){
                $value['id_number'] = strtoupper($value['id_number']);
        }
        if(array_key_exists("mtp_number",$value)){
                $value['mtp_number'] = strtoupper($value['mtp_number']);
        }
        if(array_key_exists("visa_number",$value)){
                $value['visa_number'] = strtoupper($value['visa_number']);
        }
        if(array_key_exists("passport_number",$value)){
                $value['passport_number'] = strtoupper($value['passport_number']);
        }

        // foreach($value as $key => $val) {
        //     if($key === 'mtp_number' || $key === 'visa_number' || $key === 'id_number' || $key === 'passport_number'){
        //         $val = strtoupper($val);
        //     }
        // }
        return $value;
    }

    public function gender_transition($data) { // 性別轉換成資料庫儲存型態
        if($data === 'male' || $data === '男'){
            $data = 1;
        }
        else if($data === 'female' || $data === '女'){
            $data = 2;
        }
        else if($data === 1){
            $data === 'male';
        }
        else if($data === 2){
            $data === 'female';
        }
        return $data;
    }

}
