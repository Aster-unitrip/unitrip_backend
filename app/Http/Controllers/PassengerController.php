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


    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->rule = [
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
    }

    public function get_by_id($id)
    {   //$id => 訂單id
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

    public function edit(Request $request){

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->rule);

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
        // if($owned_by !== $order_data['owned_by']){
        //     return response()->json(['error' => 'you are not an employee of this company.'], 400);
        // }


        // 判斷新增或是修改
        if(array_key_exists("_id", $validated)){ //修改
            $result = $this->requestService->update_one('passengers', $validated);
        }
        else if(!array_key_exists("_id", $validated)){ //新增
            // 新增於 passenger_profile 中
            $data_add_to_passenger_profile = $this -> ensure_passenger_profile_key($validated);
            $content = $this->requestService->insert_one('passenger_profile', $data_add_to_passenger_profile);
            $data_add_to_passenger_profile = json_decode($content->getContent(), true);

            // 補上passenger_profile 該旅客id，新增於 passengers 中
            $data_add_to_passenger = $this -> ensure_passengers_key($validated, $data_add_to_passenger_profile["inserted_id"]);
            $result = $this->requestService->insert_one('passengers', $data_add_to_passenger);
        }
        return $result;
    }

    public function list(Request $request)
    {
        // 1-1 使用者公司必須是旅行社

        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

    }

    // 刪除不必要的 key，避免回傳不該傳的資料
    public function ensure_passenger_profile_key($data) {

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

        return $data;
    }

    // 刪除不必要的 key，避免回傳不該傳的資料
    public function ensure_passengers_key($data, $inserted_id) {
        $data['is_representative'] = false;
        $data['owned_by'] = auth()->user()->company_id;
        $data['passenger_profile_id'] = $inserted_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');

        return $data;
    }

    // 姓名轉換(過渡期)
    public function ensure_name_key($name) {

        if(gettype($name) === 'string') {
            $name_changed['first_name'] = $name;
            $name_changed['last_name'] = "";
        }

        return $name_changed;
    }

}
