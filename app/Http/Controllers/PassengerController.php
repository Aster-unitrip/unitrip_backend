<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;

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
            'name_en' => 'required|string|max:30',
            'nationality' => 'required|string',
            'company' => 'string|max:50',
            'gender' => 'required|string|max:50',
            'id_number' => 'required|string|max:50',
            'passport_number' => 'string|max:50',
            'birthday' => 'required|string|date',
            'is_vegetarian' => 'boolean',
            'email' => 'email',
            'phone' => 'required|string',
            'job' => 'string|max:50',
            'needs' => 'string|max:500',
            'address' => 'required|array',
        ];
    }

    public function get_by_id($id)
    {   //$id => 訂單id
        // 1-1 使用者公司必須是旅行社

        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        $order = $this->requestService->get_one('cus_orders', $id);
        $order_data = json_decode($order->getContent(), true);
        if($user_company_id !== $order_data['user_company_id']){
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

        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
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
        if($user_company_id !== $order_data['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // 判斷新增或是修改
        if(array_key_exists("_id", $validated)){ //修改
            $result = $this->requestService->update_one('passengers', $validated);
            return $result;
        }
        if(!array_key_exists("_id", $validated)){ //新增
            $validated['is_representative'] = false;
            $validated['owned_by'] = $user_company_id;
            $passengers_new = $this->requestService->insert_one('passengers', $validated);
            return $passengers_new;
        }
    }

}
