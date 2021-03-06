<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\RequestStatesService;
use App\Services\RequestCostService;
use App\Services\OrderService;

use Validator;

class ItineraryGroupController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestPService, RequestStatesService $requestStatesService, RequestCostService $requestCostService)
    {
        $this->middleware('auth');
        $this->requestService = $requestPService;
        $this->requestStatesService = $requestStatesService;
        $this->requestCostService = $requestCostService;

        $this->edit_rule = [
            '_id'=>'string|max:24', //required
            'owned_by'=>'required|integer',
            'order_id' => 'required|string',
            'name' => 'required|string|max:30',
            'summary' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
            'total_day' => 'required|integer|between:1,30',
            'areas' => 'nullable|array',
            'people_threshold' => 'required|integer|min:1',
            'people_full' => 'required|integer|max:100',
            'sub_categories' => 'nullable|array',
            'itinerary_content' => 'required|array|min:1',
            'guides' => 'present|array',
            'transportations' => 'present|array',
            'misc' => 'present|array',
            'accounting' => 'required|array',
            'include_description' => 'required|string',
            'exclude_description' => 'required|string',
            'itinerary_group_cost' => 'required|numeric',
            'itinerary_group_price' => 'required|numeric',
            'itinerary_group_note' => 'string',
            'estimated_travel_start' => 'required|string',
            'estimated_travel_end' => 'required|string',
        ];

        $this->operator_rule = [
            '_id' => 'required|string|max:24',
            'type' => 'required|string',
            'date' => 'required|date',
            'sort' => 'required|integer',
            'pay_deposit' => 'required|string',
            'booking_status' => 'required|string',
            'payment_status' => 'required|string',
            'deposit' => 'numeric',
            /* 'balance' => 'required|numeric', */
            "amount" => 'required|numeric',
            "operator_note" => 'string',
            "travel_start" => 'required|date',
            "owned_by" => 'required|integer',
        ];
        $this->edit_delete_items = [
            "booking_status" => 'required|string',
            "payment_status" => 'required|string',
            "_id" => 'required|string'
        ];
    }

    public function edit(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正 -> 關聯 get_id
        if($owned_by !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // 判斷行程代碼是否重複 : 同公司不存在相同行程代碼，為空則不理
        if(array_key_exists('code', $validated)){
            $filter_code["code"] = $validated['code'];
            $filter_code["owned_by"] = $validated['owned_by'];
            $result_code = $this->requestService->aggregate_search('itinerary_group', null, $filter_code, $page=0);
            $result_code_data = json_decode($result_code->getContent(), true);
        }else $validated['code'] = null;

        if(array_key_exists('name', $validated)){
            $filter_name['name'] = $validated['name'];
            $filter_name["owned_by"] = $validated['owned_by'];
            $result_itinerary_group_name = $this->requestService->aggregate_search('itinerary_group', null, $filter_name, $page=0);
            $result_itinerary_group_name_data = json_decode($result_itinerary_group_name->getContent(), true);
        }else{
            return response()->json(['error' => '沒有填團行程名稱', 400]);
        }

        // 確定給的order_id是有的
        if(array_key_exists('order_id', $validated)){
            $cus_orders_data = $this->requestService->get_one('cus_orders', $validated['order_id']);
            $cus_orders_data = json_decode($cus_orders_data->getContent(), true);
            if(array_key_exists('count', $cus_orders_data) && $cus_orders_data['count'] === 0){
                return response()->json(['error' => '不存在這筆 [order_id]。'], 400);
            }
        }else{
            return response()->json(['error' => '請補上 [order_id] 這個欄位。'], 400);
        }

        if(!array_key_exists('_id', $validated)){
            // 3.1(新增團行程)
            // code 新建時 同公司不可有
            if($validated['code']!== null && $result_code_data["count"] > 0){
                return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
            }
            if($result_itinerary_group_name_data["count"] > 0){
                return response()->json(['error' => '同間公司不可有重複的行程名稱'], 400);
            }
            // 處理時間
            $validated['travel_start'] = $validated['travel_start']."T00:00:00.000+08:00";
            $validated['travel_end'] = $validated['travel_end']."T23:59:59.000+08:00";
            // travel_end 不可小於 travel_end
            if(strtotime($validated['travel_end']) - strtotime($validated['travel_start']) < 0){
                return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'], 400);
            }
            // 處理分割項目，順便處理價錢
            // 所有項目總合
            $amount_validated["total"] = 0;
            $amount_validated["adult"] = 0;
            $amount_validated["child"] = 0;

            if(array_key_exists('itinerary_content', $validated)){
                for($i = 0; $i < count($validated['itinerary_content']); $i++){
                    $validated['itinerary_content'][$i]['sort'] = $i+1;
                    // $date_add_days = date("Y-m-d H:i:s", strtotime($validated['travel_start'].$i."day"));
                    // $validated['itinerary_content'][$i]['date'] = date("Y-m-d H:i:s", strtotime($date_add_days."-8 hours"));
                    $validated['itinerary_content'][$i]['date'] = date("Y-m-d H:i:s", strtotime($validated['travel_start'].$i."day"));
                    if(array_key_exists('components', $validated['itinerary_content'][$i])){
                        for($j = 0; $j < count($validated['itinerary_content'][$i]['components']); $j++){
                            $validated['itinerary_content'][$i]['components'][$j]['sort'] = $j+1;
                            $validated['itinerary_content'][$i]['components'][$j]['operator_note'] = null;
                            $validated['itinerary_content'][$i]['components'][$j]['pay_deposit'] = 'false';
                            $validated['itinerary_content'][$i]['components'][$j]['booking_status'] = "未預訂";
                            $validated['itinerary_content'][$i]['components'][$j]['payment_status'] = "未付款";
                            $validated['itinerary_content'][$i]['components'][$j]['deposit'] = 0;
                            $validated['itinerary_content'][$i]['components'][$j]['balance'] = $validated['itinerary_content'][$i]['components'][$j]['sum'];
                            $validated['itinerary_content'][$i]['components'][$j]['amount'] = $validated['itinerary_content'][$i]['components'][$j]['sum'];
                            $validated['itinerary_content'][$i]['components'][$j]['actual_payment'] = 0;
                            $validated['itinerary_content'][$i]['components'][$j]['date'] = $validated['itinerary_content'][$i]['date'];
                            $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['itinerary_content'][$i]['components'][$j]['type'], $validated['itinerary_content'][$i]['components'][$j]);
                            $amount_validated['total'] += $amount['total'];

                        }
                    }
                }
            }
            if(array_key_exists('guides', $validated)){
                for($i = 0; $i < count($validated['guides']); $i++){
                    $validated['guides'][$i]['sort'] = $i+1;
                    $validated['guides'][$i]['operator_note'] = null;
                    $validated['guides'][$i]['pay_deposit'] = 'false';
                    $validated['guides'][$i]['booking_status'] = "未預訂"; //預定狀態
                    $validated['guides'][$i]['payment_status'] = "未付款";
                    $validated['guides'][$i]['deposit'] = 0;
                    $validated['guides'][$i]['balance'] = $validated['guides'][$i]['subtotal'];
                    $validated['guides'][$i]['amount'] = $validated['guides'][$i]['subtotal'];
                    $validated['guides'][$i]['actual_payment'] = 0;
                    $validated['guides'][$i]['date_start'] = $validated['guides'][$i]['date_start']."T00:00:00.000+08:00";
                    $validated['guides'][$i]['date_end'] = $validated['guides'][$i]['date_end']."T23:59:59.000+08:00";
                    if(strtotime($validated['guides'][$i]['date_end']) - strtotime($validated['guides'][$i]['date_start']) <= 0){
                        return response()->json(['error' => '(導遊)結束時間不可早於開始時間'], 400);
                    }
                    if(strtotime($validated['guides'][$i]['date_end']) - strtotime($validated['travel_end']) > 0){
                        return response()->json(['error' => '導遊結束時間不可晚於旅程期間'], 400);
                    }
                    if(strtotime($validated['guides'][$i]['date_start']) - strtotime($validated['travel_start']) < 0){
                        return response()->json(['error' => '導遊開始時間不可早於旅程期間'], 400);
                    }
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['guides'][$i]['type'], $validated['guides'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }
            if(array_key_exists('transportations', $validated)){
                for($i = 0; $i < count($validated['transportations']); $i++){
                    $validated['transportations'][$i]['sort'] = $i+1;
                    $validated['transportations'][$i]['operator_note'] = null;
                    $validated['transportations'][$i]['pay_deposit'] = 'false';
                    $validated['transportations'][$i]['booking_status'] = "未預訂"; //預定狀態
                    $validated['transportations'][$i]['payment_status'] = "未付款";
                    $validated['transportations'][$i]['deposit'] = 0;
                    $validated['transportations'][$i]['balance'] = $validated['transportations'][$i]['sum'];
                    $validated['transportations'][$i]['amount'] = $validated['transportations'][$i]['sum'];
                    $validated['transportations'][$i]['actual_payment'] = 0;
                    $validated['transportations'][$i]['date_start'] = $validated['transportations'][$i]['date_start']."T00:00:00.000+08:00";
                    $validated['transportations'][$i]['date_end'] = $validated['transportations'][$i]['date_end']."T23:59:59.000+08:00";
                    if(strtotime($validated['transportations'][$i]['date_end']) - strtotime($validated['transportations'][$i]['date_start']) <= 0){
                        return response()->json(['error' => '(交通工具)結束時間不可早於開始時間'], 400);
                    }
                    if(strtotime($validated['transportations'][$i]['date_end']) - strtotime($validated['travel_end']) > 0){
                        return response()->json(['error' => '交通工具結束時間不可晚於旅程期間'], 400);
                    }
                    if(strtotime($validated['transportations'][$i]['date_start']) - strtotime($validated['travel_start']) < 0){
                        return response()->json(['error' => '交通工具開始時間不可早於旅程期間'], 400);
                    }
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['transportations'][$i]['type'], $validated['transportations'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }
            if(array_key_exists('misc', $validated)){
                for($i = 0; $i < count($validated['misc']); $i++){
                    $validated['misc'][$i]['sort'] = $i+1;
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['misc'][$i]['type'], $validated['misc'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }
            $validated['operator_note']= "";
            if(!array_key_exists('itinerary_group_note', $validated)){
                $validated['itinerary_group_note'] = "";
            }

            if($amount_validated['total'] !== $validated['itinerary_group_cost']){
                return response()->json(['error' => "所有元件加總不等於總直成本(itinerary_group_cost)"], 400);
            }

            $itinerary_group_new = $this->requestService->insert_one('itinerary_group', $validated);
            $result_data = json_decode($itinerary_group_new->getContent(), true);

            // 找出團行程的 order_id，去修改 order itinerary_group_id、cus_group_code
            $itinerary_group = $this->requestService->get_one('itinerary_group', $result_data['inserted_id']);
            $itinerary_group_data = json_decode($itinerary_group->getContent(), true);
            $order = $this->requestService->get_one('cus_orders', $validated["order_id"]);
            $order_data = json_decode($order->getContent(), true);

            //處理created_at:2022-03-09T17:52:30 -> 20220309_
            $created_at_date = substr($order_data["created_at"], 0, 10);
            $created_at_time = substr($order_data["created_at"], 11);
            $created_at_date = preg_replace('/-/', "", $created_at_date);
            $created_at_time = preg_replace('/:/', "", $created_at_time);

            //CUS_"行程代碼"_"旅行社員工id"_"客製團訂單日期"_"客製團訂單時間"_"行程天數"_"第幾團"
            if(array_key_exists('code', $itinerary_group_data)){
                $fixed["cus_group_code"] = "CUS_".$itinerary_group_data['code']."_".$order_data["owned_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
            }else if(!array_key_exists('code', $itinerary_group_data) && $validated['code'] !== null){
                $fixed["cus_group_code"] = "CUS_".$order_data["owned_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
            }

            $fixed["_id"] = $order_data["_id"];
            $fixed["itinerary_group_id"] = $result_data['inserted_id'];
            $fixed["amount"] = $itinerary_group_data['itinerary_group_price'];


            $result = $this->requestService->update_one('cus_orders', $fixed);
            return $result;

        }
        else if(array_key_exists('_id', $validated)){
            //3.2(編輯團行程)
            if($validated['code']!== null && $result_code_data["count"] > 1){
                if($result_code_data["docs"][0]['_id'] !== $validated['_id']){
                    return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
                }
            }
            if($result_itinerary_group_name_data["count"] > 1){
                if($result_itinerary_group_name_data["docs"][0]['_id'] !== $validated['_id']){
                    return response()->json(['error' => '同間公司不可有重複的行程名稱'], 400);
                }
            }
            // 處理時間
            $validated['travel_start'] = $validated['travel_start']."T00:00:00.000+08:00";
            $validated['travel_end'] = $validated['travel_end']."T23:59:59.000+08:00";

            if(strtotime($validated['travel_end']) - strtotime($validated['travel_start']) < 0){
                return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'. strtotime($validated['travel_end']) - strtotime($validated['travel_start'])], 400);
            }

            $amount_validated["total"] = 0;

            if(array_key_exists('itinerary_content', $validated)){
                for($i = 0; $i < count($validated['itinerary_content']); $i++){

                    $validated['itinerary_content'][$i]['date'] = date("Y-m-d H:i:s", strtotime($validated['travel_start'].$i."day"));
                    // 日期重新組合
                    if(!array_key_exists('sort', $validated['itinerary_content'][$i])){
                        $validated['itinerary_content'][$i]['sort'] = $i+1;
                    }
                    // 當日行程重新組合
                    if(array_key_exists('components', $validated['itinerary_content'][$i])){
                        for($j = 0; $j < count($validated['itinerary_content'][$i]['components']); $j++){
                            $validated['itinerary_content'][$i]['components'][$j]['date'] = $validated['itinerary_content'][$i]['date'];
                            if(!array_key_exists('sort', $validated['itinerary_content'][$i]['components'][$j])){
                                // $validated['itinerary_content'][$i]['components'][$j]['sort'] = $j+1;
                                $validated['itinerary_content'][$i]['components'][$j]['operator_note'] = null;
                                $validated['itinerary_content'][$i]['components'][$j]['pay_deposit'] = 'false';
                                $validated['itinerary_content'][$i]['components'][$j]['booking_status'] = "未預訂";
                                $validated['itinerary_content'][$i]['components'][$j]['payment_status'] = "未付款";
                                $validated['itinerary_content'][$i]['components'][$j]['deposit'] = 0;
                                $validated['itinerary_content'][$i]['components'][$j]['balance'] = $validated['itinerary_content'][$i]['components'][$j]['sum'];
                                $validated['itinerary_content'][$i]['components'][$j]['amount'] = $validated['itinerary_content'][$i]['components'][$j]['sum'];
                                $validated['itinerary_content'][$i]['components'][$j]['actual_payment'] = 0;
                            }
                            $validated['itinerary_content'][$i]['components'][$j]['sort'] = $j+1;
                        }
                    }

                    // 驗算
                    if(array_key_exists('components', $validated['itinerary_content'][$i])){
                        for($j = 0; $j < count($validated['itinerary_content'][$i]['components']); $j++){
                            $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['itinerary_content'][$i]['components'][$j]['type'], $validated['itinerary_content'][$i]['components'][$j]);
                            $amount_validated['total'] += $amount['total'];
                        }
                    }

                }
            }

            if(array_key_exists('guides', $validated)){
                for($i = 0; $i < count($validated['guides']); $i++){
                    $validated['guides'][$i]['date_start'] = $validated['guides'][$i]['date_start']."T00:00:00.000+08:00";
                    $validated['guides'][$i]['date_end'] = $validated['guides'][$i]['date_end']."T23:59:59.000+08:00";
                    if(strtotime($validated['guides'][$i]['date_end']) - strtotime($validated['guides'][$i]['date_start']) <= 0){
                        return response()->json(['error' => '(導遊)結束時間不可早於開始時間'], 400);
                    }
                    if(strtotime($validated['guides'][$i]['date_end']) - strtotime($validated['travel_end']) > 0){
                        return response()->json(['error' => '導遊結束時間不可晚於旅程期間'], 400);
                    }
                    if(strtotime($validated['guides'][$i]['date_start']) - strtotime($validated['travel_start']) < 0){
                        return response()->json(['error' => '導遊開始時間不可早於旅程期間'], 400);
                    }
                    //新增後改值
                    if(!array_key_exists('sort', $validated['guides'][$i])){
                        // $validated['guides'][$i]['sort'] = $i+1;
                        $validated['guides'][$i]['operator_note'] = null;
                        $validated['guides'][$i]['pay_deposit'] = 'false';
                        $validated['guides'][$i]['booking_status'] = "未預訂"; //預定狀態
                        $validated['guides'][$i]['payment_status'] = "未付款";
                        $validated['guides'][$i]['deposit'] = 0;
                        $validated['guides'][$i]['balance'] = $validated['guides'][$i]['subtotal'];
                        $validated['guides'][$i]['amount'] = $validated['guides'][$i]['subtotal'];
                        $validated['guides'][$i]['actual_payment'] = 0;
                    }
                    $validated['guides'][$i]['sort'] = $i+1;
                }
                // 驗算
                for($i = 0; $i < count($validated['guides']); $i++){
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['guides'][$i]['type'], $validated['guides'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }

            if(array_key_exists('transportations', $validated)){
                for($i = 0; $i < count($validated['transportations']); $i++){
                    $validated['transportations'][$i]['date_start'] = $validated['transportations'][$i]['date_start']."T00:00:00.000+08:00";
                    $validated['transportations'][$i]['date_end'] = $validated['transportations'][$i]['date_end']."T23:59:59.000+08:00";
                    if(strtotime($validated['transportations'][$i]['date_end']) - strtotime($validated['transportations'][$i]['date_start']) <= 0){
                        return response()->json(['error' => '(交通工具)結束時間不可早於開始時間'], 400);
                    }
                    if(strtotime($validated['transportations'][$i]['date_end']) - strtotime($validated['travel_end']) > 0){
                        return response()->json(['error' => '交通工具結束時間不可晚於旅程期間'], 400);
                    }
                    if(strtotime($validated['transportations'][$i]['date_start']) - strtotime($validated['travel_start']) < 0){
                        return response()->json(['error' => '交通工具開始時間不可早於旅程期間'], 400);
                    }
                    //新增後改值
                    if(!array_key_exists('sort', $validated['transportations'][$i])){
                        // $validated['transportations'][$i]['sort'] = $i+1;
                        $validated['transportations'][$i]['operator_note'] = null;
                        $validated['transportations'][$i]['pay_deposit'] = 'false';
                        $validated['transportations'][$i]['booking_status'] = "未預訂"; //預定狀態
                        $validated['transportations'][$i]['payment_status'] = "未付款";
                        $validated['transportations'][$i]['deposit'] = 0;
                        $validated['transportations'][$i]['balance'] = $validated['transportations'][$i]['sum'];
                        $validated['transportations'][$i]['amount'] = $validated['transportations'][$i]['sum'];
                        $validated['transportations'][$i]['actual_payment'] = 0;
                    }
                    $validated['transportations'][$i]['sort'] = $i+1;
                }
                // 驗算
                for($i = 0; $i < count($validated['transportations']); $i++){
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['transportations'][$i]['type'], $validated['transportations'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }

            if(array_key_exists('misc', $validated)){
                for($i = 0; $i < count($validated['misc']); $i++){
                    $validated['misc'][$i]['sort'] = $i+1;
                }
                // 驗算
                for($i = 0; $i < count($validated['misc']); $i++){
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['misc'][$i]['type'], $validated['misc'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }

            if($amount_validated['total'] !== $validated['itinerary_group_cost']){
                return response()->json(['error' => "驗算總值成本: ".$amount_validated['total']."，前端傳來總值成本: ".$validated['itinerary_group_cost']."；所有元件加總不等於總直成本(itinerary_group_cost)"], 400);
            }

            // 取得 created_at
            $itinerary_group_old_data = $this->requestService->get_one('itinerary_group', $validated['_id']);
            $itinerary_group_old_data = json_decode($itinerary_group_old_data->getContent(), true);
            $validated['created_at'] = $itinerary_group_old_data['created_at'];

            $itinerary_group_update_data = $this->requestService->update('itinerary_group', $validated);

            $fixed["_id"] = $validated["order_id"];
            $fixed["itinerary_group_id"] = $validated['_id'];
            $fixed["amount"] = $validated['itinerary_group_price'];

            $result = $this->requestService->update_one('cus_orders', $fixed);
            return $itinerary_group_update_data;
        }
    }

    // filter: 區域, 行程名稱, 子類別, 天數起訖, 成團人數, 滿團人數, 頁數
    // _id, name, areas, sub_categories, total_day, people_threshold, people_full, page

    // project: ID, 名稱, 子類別, 行程天數, 成團人數, 建立日期

    public function list(Request $request)
    {
        $filter = json_decode($request->getContent(), true);
        if (array_key_exists('page', $filter)) {
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

        // Handle itinerary order_number
        if (array_key_exists('order_number', $filter)) {
            $filter['order_number'] = array('$regex' => trim($filter['order_number']));
        }

        // Handle itinerary representative
        if (array_key_exists('representative', $filter)) {
            $filter['representative'] = array('$regex' => trim($filter['representative']));
        }

        // Handle itinerary cus_group_code
        if (array_key_exists('cus_group_code', $filter)) {
            $filter['cus_group_code'] = array('$regex' => trim($filter['cus_group_code']));
        }

        // Handle order created_at range query
        if(array_key_exists('order_start', $filter) && array_key_exists('order_end', $filter)){
            if(strtotime($filter['order_end']) - strtotime($filter['order_start']) >= 0){
                $filter['created_at'] = array('$gte' => $filter['order_start']."T00:00:00.000+08:00"
                , '$lte' => $filter['order_end']."T23:59:59.000+08:00");
            }
            else return response()->json(['error' => '訂購結束時間不可早於訂購開始時間'], 400);
        }
        else if(array_key_exists('order_start', $filter) && !array_key_exists('order_end', $filter)){
            return response()->json(['error' => '沒有訂購結束時間'], 400);
        }
        else if(!array_key_exists('order_start', $filter) && array_key_exists('order_end', $filter)){
            return response()->json(['error' => '沒有訂購開始時間'], 400);
        }
        unset($filter['order_start']);
        unset($filter['order_end']);

        $company_type = auth()->payload()->get('company_type');
        if ($company_type == 1){
        }elseif ($company_type == 2){
            $filter['owned_by'] = auth()->user()->company_id;
        }else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        // Handle itinerary travel_start、travel_end range query
        if(array_key_exists('travel_start', $filter) && array_key_exists('travel_end', $filter)){
            if(strtotime($filter['travel_end']) - strtotime($filter['travel_start']) >= 0){
                $filter['travel_start'] = $filter['travel_start']."T00:00:00.000+08:00";
                $filter['travel_end'] = $filter['travel_end']."T23:59:59.000+08:00";
            }else return response()->json(['error' => '行程結束時間不可早於行程開始時間'], 400);

        }elseif(array_key_exists('travel_start', $filter) && !array_key_exists('travel_end', $filter)){
            return response()->json(['error' => '沒有訂購結束時間'], 400);
        }elseif(!array_key_exists('travel_start', $filter) && array_key_exists('travel_end', $filter)){
            return response()->json(['error' => '沒有訂購開始時間'], 400);
        }

        //sort by [created_at]、[travel_start]
        if(array_key_exists('sort', $filter)){
            // 轉換sort
            $filter["searchSort"] = $this->requestStatesService->change_search_sort($filter['sort']);
            unset($filter['sort']);
        }else{
            $filter["searchSort"]["created_at"] = -1;
        }

        $result = $this->requestService->aggregate_search('cus_orders_join_itinerary_group', null, $filter, $page);
        $result_data =  json_decode($result->content(), true);

        for($i = 0; $i < count($result_data['docs']); $i++){
            if(!isset($result_data['docs'][$i]['travel_start'])){
                $result_data['docs'][$i]['travel_start'] = "";
                $result_data['docs'][$i]['travel_end'] = "";
            }
        }

        return $result_data;
    }

    public function get_by_id($id)
    { //傳入訂單id
        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $contact_name = auth()->user()->contact_name;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        $cus_order = $this->requestService->get_one('cus_orders', $id);
        $cus_order_data =  json_decode($cus_order->content(), true);

        if(array_key_exists('count', $cus_order_data) && $cus_order_data['count'] === 0){
            return response()->json(['error' => '訂單中團行程id可能不存在或已刪除。'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        if($owned_by !== $cus_order_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        if($cus_order_data['itinerary_group_id']){ //old
            //更新訂單相關資訊

            $itinerary_group = $this->requestService->get_one('itinerary_group', $cus_order_data['itinerary_group_id']);
            $itinerary_group_data =  json_decode($itinerary_group->content(), true);
            // 如果已經沒有該團行程 需要額外處理
            if(array_key_exists('count', $itinerary_group_data) && $itinerary_group_data['count'] === 0){
                return response()->json(['error' => '訂單中團行程id可能已過期(團行程刪除)。'], 400);
            }

            // 修改因訂單改變而修改的團行程資料
            $update_order_data['estimated_travel_start'] = $cus_order_data['estimated_travel_start'].".000+08:00";
            $update_order_data['estimated_travel_end'] = $cus_order_data['estimated_travel_end'].".000+08:00";
            $update_order_data['total_day'] = $cus_order_data['total_day'];
            $update_order_data['_id'] = $cus_order_data['itinerary_group_id'];

            //如果預估旅行時間不符合旅行時間(旅行時間沒有在預估旅行時間內)，傳回空值
            $ac["estimated_travel_start"] = strtotime($cus_order_data['estimated_travel_start']);
            $ac["estimated_travel_end"] = strtotime($cus_order_data['estimated_travel_end']);
            $ac["travel_start"] = strtotime($itinerary_group_data['travel_start']);
            $ac["travel_end"] = strtotime($itinerary_group_data['travel_end']);
            if(($ac["estimated_travel_start"] > $ac["travel_start"]) || ($ac["estimated_travel_end"] < $ac["travel_end"])){
                $update_order_data["travel_start"] = "";
                $update_order_data["travel_end"] = "";
            }
            if($cus_order_data['total_day'] < $itinerary_group_data['total_day']){
                $update_order_data["travel_start"] = "";
                $update_order_data["travel_end"] = "";
            }

            // TODO 判斷團行程object數量是否等於天數
            for($i = 0; $i < count($itinerary_group_data['itinerary_content']); $i++){
                if($cus_order_data['total_day'] <= $i){
                    unset($itinerary_group_data['itinerary_content'][$i]);
                    break;
                }
                if(array_key_exists("travel_start", $update_order_data) && $update_order_data["travel_start"] === ""){
                    // 時間需要被覆蓋
                    $itinerary_group_data['itinerary_content'][$i]['date'] = "";
                    for($j = 0; $j < count($itinerary_group_data['itinerary_content'][$i]['components']); $j++){
                        $itinerary_group_data['itinerary_content'][$i]['components'][$j]['date'] = "";
                    }
                }
            }
            $update_order_data['itinerary_content'] = $itinerary_group_data['itinerary_content'];
            $this->requestService->update_one('itinerary_group', $update_order_data);

            $itinerary_group_after_edit = $this->requestService->get_one('itinerary_group', $cus_order_data['itinerary_group_id']);
            $itinerary_group_after_edit_data =  json_decode($itinerary_group_after_edit->content(), true);
            return $itinerary_group_after_edit_data;

        }
        else if(!$cus_order_data['itinerary_group_id']){ //new

            // TODO 這部分感覺可以優化
            $itinerary_group_data_new['order_id'] = $cus_order_data['_id'];
            $itinerary_group_data_new['name'] = "";
            $itinerary_group_data_new['summary'] = "";
            $itinerary_group_data_new['code'] = "";
            $itinerary_group_data_new['travel_start'] = "";
            $itinerary_group_data_new['travel_end'] = "";
            $itinerary_group_data_new['estimated_travel_start'] = $cus_order_data['estimated_travel_start'];
            $itinerary_group_data_new['estimated_travel_end'] = $cus_order_data['estimated_travel_end'];
            $itinerary_group_data_new['total_day'] = $cus_order_data['total_day'];
            $itinerary_group_data_new['areas'] = array();
            $itinerary_group_data_new['sub_categories'] = array();
            $itinerary_content_new['type'] = "";
            $itinerary_content_new['name'] = "";
            $itinerary_content_new['gather_time'] = "";
            $itinerary_content_new['gather_location'] = "";
            $itinerary_content_new['date'] = "";
            $itinerary_content_new['day_summary'] = "";
            $itinerary_content_new['components'] = array();
            $itinerary_group_data_new['itinerary_content'] = array($itinerary_content_new);
            $itinerary_group_data_new['people_threshold'] = 1;
            $itinerary_group_data_new['people_full'] = 10;
            $itinerary_group_data_new['guides'] = array();
            $itinerary_group_data_new['transportations'] = array();
            $itinerary_group_data_new['misc'] = array();
            $account_array['cost'] = 0;
            $account_array['estimation_price'] = 0;
            $account['adult'] = array($account_array);
            $account['child'] = array($account_array);
            $itinerary_group_data_new['accounting'] = array($account);
            $itinerary_group_data_new['itinerary_group_cost'] = 0;
            $itinerary_group_data_new['itinerary_group_price'] = 0;
            $itinerary_group_data_new['include_description'] = "";
            $itinerary_group_data_new['exclude_description'] = "";
            $itinerary_group_data_new['last_updated_on'] = $contact_name;
            $itinerary_group_data_new['itinerary_group_note'] = "";
            $itinerary_group_data_new['owned_by'] = $owned_by;
            return $itinerary_group_data_new;
        }
        else{
            return response()->json(['error' => '發生一些問題，可能是資料沒給正確!']);
        }
    }


    public function get_component_type($id)
    { //團行程ID
        // 非旅行社及該旅行社人員不可修改訂單
        $data_before = $this->requestService->find_one('itinerary_group', $id, null, null);
        if(!$data_before){
            return response()->json(['error' => '輸入id搜尋不到團行程。'], 400);
        }

        $data_before = $data_before['document'];
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($owned_by !== $data_before['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $result = $this->requestService->get_one('itinerary_group_groupby_component_type', $id);
        return $result;

    }


    public function save_to_itinerary(Request $request)
    { //將團行程存回行程範本
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        if($owned_by !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO 團行程[名稱]、[代碼]必須和行程不同
        //$filter['company_id'] = $owned_by;
        $filter['code'] = $validated['code'];
        $filter['name'] = $validated['name'];
        $result_code = $this->requestService->aggregate_search('itineraries', null, $filter, $page=0);
        $result_code_data = json_decode($result_code->getContent(), true);
        if($result_code_data["count"] > 0){
            // 代表有重複的
            return response()->json(['error' => "[行程代碼]或是[行程編號]已重複。"], 400);
        }


        // TODO 將團行程處理成行程存下來
        // 1.刪除一些第一層欄位
        unset($validated['order_id']);
        unset($validated['travel_start']);
        unset($validated['travel_end']);
        unset($validated['itinerary_group_cost']);
        unset($validated['itinerary_group_price']);
        unset($validated['itinerary_group_note']);

        // 2.刪除guides
        if(array_key_exists('guides', $validated)){
            for($i = 0; $i < count($validated['guides']); $i++){
                unset($validated['guides'][$i]['date_start']);
                unset($validated['guides'][$i]['date_end']);
                unset($validated['guides'][$i]['pay_deposit']);
                unset($validated['guides'][$i]['booking_status']);
                unset($validated['guides'][$i]['payment_status']);
                unset($validated['guides'][$i]['deposit']);
                unset($validated['guides'][$i]['balance']);
                unset($validated['guides'][$i]['actual_payment']);
                unset($validated['guides'][$i]['operator_note']);
            }
        }
        // 2.刪除transportations
        if(array_key_exists('transportations', $validated)){
            for($i = 0; $i < count($validated['transportations']); $i++){
                unset($validated['transportations'][$i]['date_start']);
                unset($validated['transportations'][$i]['date_end']);
                unset($validated['transportations'][$i]['pay_deposit']);
                unset($validated['transportations'][$i]['booking_status']);
                unset($validated['transportations'][$i]['payment_status']);
                unset($validated['transportations'][$i]['deposit']);
                unset($validated['transportations'][$i]['balance']);
                unset($validated['transportations'][$i]['actual_payment']);
                unset($validated['transportations'][$i]['operator_note']);
            }
        }
        // 3.刪除 itinerary_content
        if(array_key_exists('itinerary_content', $validated)){
            for($i = 0; $i < count($validated['itinerary_content']); $i++){
                unset($validated['itinerary_content'][$i]['date']);
                if(array_key_exists('components', $validated['itinerary_content'][$i])){
                    for($j = 0; $j < count($validated['itinerary_content'][$i]['components']); $j++){
                        unset($validated['itinerary_content'][$i]['components'][$j]['date']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['pay_deposit']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['booking_status']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['payment_status']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['deposit']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['balance']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['actual_payment']);
                        unset($validated['itinerary_content'][$i]['components'][$j]['operator_note']);
                    }
                }
            }
        }

        // 4.將行程存下來 // TODO: 無法成功存下來
        $itinerary_new = $this->requestService->insert_one('itineraries', $validated);
        return $itinerary_new;

    }


    public function operator(Request $request)
    { //傳團行程
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->operator_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 1.使用者公司必須是旅行社、限制只能同公司員工作修正
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($owned_by !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // 基本驗證 所有和金額有關必須不小於0
        if($validated['deposit'] < 0 && $validated['amount'] < 0){
            return response()->json(['error' => '金額相關不會小於0']);
        }

        // 2.處理前端傳來的資料 確認是否有這筆團行程
        $itinerary_group_past = $this->requestService->find_one('itinerary_group', $validated['_id'], null, null);
        if(!$itinerary_group_past) return response()->json(['error' => "沒有這筆團行程"], 400);
        $itinerary_group_past_data = $itinerary_group_past['document'];

        // order 找 order_status ?== 已成團
        $itinerary_group_order_data = $this->requestService->find_one('cus_orders', null, 'itinerary_group_id', $validated['_id']);
        if(!$itinerary_group_order_data){
            return response()->json(['error' => "團行程沒有關聯的訂單"], 400);
        }

        // 修改付款狀態
        if(array_key_exists("date", $validated) && array_key_exists("sort", $validated)){
            if($validated["sort"]<=0){
                return response()->json(['error' => "sort 必須大於0。"]);
            }
            $find_day = (int)(floor((strtotime($validated["date"]) - strtotime($validated['travel_start'])) / (60*60*24))); //將 date 做轉換成第幾天

            $find_sort = $validated["sort"]-1; // sort比原來少1
            if(array_key_exists("type", $validated)){
                if($validated["type"] === "attractions" || $validated["type"] === "accomendations" || $validated["type"] === "activities" || $validated["type"] === "restaurants"){
                    $find_type = 'itinerary_content';
                    $find_name = $find_type.".".$find_day.".components.".$find_sort.".";
                    $find_name_no_dot = $find_type.".".$find_day.".components.".$find_sort;
                    $find_name_using_pull = $find_type.".".$find_day.".components";


                    if(array_key_exists($find_sort, $itinerary_group_past_data[$find_type][$find_day]['components'])){
                        if($itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort] === null){
                            return response()->json(['error' => "位於[景點]或[住宿]或[活動]或[餐廳]元件中，元件內容為 ".$itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]." ，找不到該筆元件資訊。"], 400);
                        }
                        else if($itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]['type'] !== $validated['type']){
                            return response()->json(['error' => "元件類型為 ".$itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]['type']." ，搜尋類型為" .$validated['type']." 兩者有誤，可能是[date]欄位有問題導致。"], 400);
                        }

                    }
                }
                else if($validated["type"] === "transportations" || $validated["type"] === "guides"){
                    $find_type =$validated["type"];
                    $find_name = $find_type.".".$find_sort.".";
                    $find_name_no_dot = $find_type.".".$find_sort;
                    $find_name_using_pull = $find_type;


                    if(array_key_exists($find_sort, $itinerary_group_past_data[$find_type])){
                        if($itinerary_group_past_data[$find_type][$find_sort] === null){
                            return response()->json(['error' => "位於[交通工具]或[導遊]元件中，元件內容為 ".$itinerary_group_past_data[$find_type][$find_sort]." ，找不到該筆元件資訊。"], 400);
                        }
                        else if($itinerary_group_past_data[$find_type][$find_sort]['type'] !== $validated['type']){
                            return response()->json(['error' => "元件類型為 ".$itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]['type']." ，搜尋類型為" .$validated['type']." 兩者有誤，可能是[date]欄位有問題導致。"], 400);
                        }
                    }
                }
            }
            else{
                return response()->json(['error' => '沒有傳回修改項目類別(type)'], 400);
            }
        }
        else{
            return response()->json(['error' => '請輸入訂購日期(date), 排序(sort)'], 400);
        }

        // 基本驗證 如果需要付訂金，改訂金
        if(array_key_exists("pay_deposit", $validated)){
            if($validated['pay_deposit'] === 'true'){
                if(array_key_exists("deposit", $validated) && $validated['deposit'] > 0){//要付訂金
                    if($validated['deposit'] > $validated['amount']){// 訂金不可大於總額
                        return response()->json(['error' => "訂金不可以大於總額"], 400);
                    }
                    $validated['balance'] = $validated['amount'] - $validated['deposit'];
                }else{
                    return response()->json(['error' => '必須存在訂金，且如選[pay_deposit=true]，金額必須大於0'], 400);
                }
            }
            else if($validated['pay_deposit'] === 'false'){
                if(array_key_exists("deposit", $validated) && $validated['deposit'] !== 0){
                    return response()->json(['error' => "當必須存在訂金，且如選[pay_deposit=false]，不可以有訂金"], 400);
                }
                $validated['balance'] = $validated['amount'];
            }
        }

        // 判斷狀態
        if($validated['payment_status'] === "已付訂金"){
            if($validated['pay_deposit'] === "false"){
                return response()->json(['error' => "付款狀態為[已付訂金]時，是否預付訂金不可為0"]);
            }
            $fixed[$find_name.'actual_payment'] = $validated['deposit'];
        }
        else if($validated['payment_status'] === "已付全額"){
            $fixed[$find_name.'actual_payment'] = $validated['amount'];
        }

        // 抓到團行程更新欄位
        $fixed['_id'] = $validated['_id'];
        $fixed[$find_name.'pay_deposit'] = $validated['pay_deposit'];
        $fixed[$find_name.'booking_status'] = $validated['booking_status'];
        $fixed[$find_name.'payment_status'] = $validated['payment_status'];
        $fixed[$find_name.'operator_note'] = $validated['operator_note'];
        $fixed[$find_name.'deposit'] = $validated['deposit'];
        $fixed[$find_name.'balance'] = $validated['balance'];


        // 先確定該欄位是否有值 確認付款狀態及預訂狀態
        if($find_type === "itinerary_content"){
            $result_booking = $this->requestStatesService->booking_status($validated, $itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]);
            if($result_booking !== true) return $result_booking;
            $result_payment = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]);
            if($result_payment !== true) return $result_payment;

        }
        else if($find_type === "transportations" || $find_type === "guides"){
            $result_booking = $this->requestStatesService->booking_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result_booking !== true) return $result_booking;
            $result_payment = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result_payment !== true) return $result_payment;
        }

        // 確定沒錯後存入團行程中
        $this->requestService->update_one('itinerary_group', $fixed);


        // 當狀態須加上刪除判斷 如果待退已退則刪除該obj放入刪除DB中
        if($validated['booking_status'] === "未預訂" || $validated['booking_status']=== "已預訂"){
            return response()->json(['success' => "更新成功!"], 200);
        }
        else if($validated['booking_status'] === "待退訂"){

            // 取得存後資料
            $operator_data = $this->requestService->get_one('itinerary_group', $validated['_id']);
            $operator_data = json_decode($operator_data->getContent(), true);

            // 判斷該筆資料type，欲處理待退訂項目
            if($find_type === "itinerary_content"){
                $to_deleted = $operator_data[$find_type][$find_day]['components'][$find_sort];
            }
            else if($find_type === "transportations" || $find_type === "guides"){
                $to_deleted = $operator_data[$find_type][$find_sort];
            }

            // 取得 客製化團 人數
            $cus_orders = $this->requestService->get_one('cus_orders', $operator_data['order_id']);
            $cus_orders_data = json_decode($cus_orders->getContent(), true);
            $total_people = $cus_orders_data['total_people'];

            // 要放入元件刪除資料庫
            $to_deleted['order_id'] = $operator_data['order_id'];
            $to_deleted['itinerary_group_id'] = $operator_data['_id'];

            $to_deleted = $this->requestStatesService->delete_data($to_deleted);

            // 加入刪除資料庫中
            $this->requestService->insert_one('cus_delete_components', $to_deleted);

            // 修改團行程成本(需要扣掉的)，目前accounting大人小孩成本尚未處理
            $delete_component_data = $this->requestCostService->after_delete_component_cost($itinerary_group_past_data["accounting"],$itinerary_group_past_data['itinerary_group_cost'], $to_deleted, $total_people);

            $this->requestService->update_one('itinerary_group', $delete_component_data);

            // 先將資料轉換
            $change_component_data['_id'] = $validated['_id'];
            $change_component_data[$find_name_no_dot] = array("a" => "1");
            $this->requestService->update_one('itinerary_group', $change_component_data);


            // 刪除團行程該元件
            $to_deleted_itinerary['_id'] = $validated['_id'];
            $to_deleted_itinerary[$find_name_using_pull] = array("a" => "1");
            $this->requestService->pull_element('itinerary_group', $to_deleted_itinerary);


            // 修正sort順序
            // 取得存後資料
            $operator_data_after_delete = $this->requestService->get_one('itinerary_group', $validated['_id']);
            $operator_data_after_delete = json_decode($operator_data_after_delete->getContent(), true);
            $update_data["_id"] = $validated['_id'];
            if($validated["type"] === "attractions" || $validated["type"] === "accomendations" || $validated["type"] === "activities" || $validated["type"] === "restaurants"){
                for($i = 0; $i < count($operator_data_after_delete['itinerary_content'][$find_day]['components']); $i++){
                    $operator_data_after_delete['itinerary_content'][$find_day]['components'][$i]["sort"] = $i+1;
                }
                $update_data['itinerary_content'] = $operator_data_after_delete['itinerary_content'];
            }
            else if($validated["type"] === "transportations" || $validated["type"] === "guides"){
                for($i = 0; $i < count($operator_data_after_delete[$validated["type"]]); $i++){
                    $operator_data_after_delete[$validated["type"]]['sort']= $i+1;
                }
                $update_data[$validated["type"]] = $operator_data_after_delete[$validated["type"]];
            }
            $this->requestService->update_one('itinerary_group', $update_data);
            return response()->json(["已成功刪除此元件、更新成本，請至團行程編輯中修改定價!"], 200);
        }
    }

    public function get_delete_items($id)
    { // 過濾出該[團行程]全部刪除內容

        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data = $this->requestService->find_one('itinerary_group', $id, null, null);
        if(!$data){
            return response()->json(['error' => '沒有這筆團行程資料。'], 400);
        }
        if($owned_by !== $data['document']['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $filter['itinerary_group_id'] = $id;
        $result_code = $this->requestService->aggregate_search('cus_delete_components', null, $filter, null);
        $result_code_data = json_decode($result_code->getContent(), true);
        return $result_code_data;

    }

    public function edit_delete_items(Request $request)
    { //修改團行程改待退後物件

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_delete_items);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 1.使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data_before = $this->requestService->find_one('cus_delete_components', $validated['_id'], null, null);
        $data_of_itinerary_group_before = $this->requestService->find_one('itinerary_group', $data_before['document']['itinerary_group_id'], null, null);
        if($owned_by !== $data_of_itinerary_group_before['document']['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // 直接針對待退已退做判斷(過去資料和送入資料比對)
        if(array_key_exists("payment_status", $validated) && array_key_exists("booking_status", $validated)){
            if($data_before['document']['booking_status'] === "待退訂"){
                if($validated['booking_status'] !== "待退訂" && $validated['booking_status'] !== "已退訂"){
                    return response()->json(['error' => '預定狀態[待退訂]只可以維持[待退訂]或是改成[已退訂]。'] , 400);
                }
                if($validated['booking_status'] === '待退訂'){
                    if($validated['payment_status'] !== '已棄單，待退款' && $validated['payment_status'] !== '已棄單，免退款'){
                        return response()->json(['error' => '預定狀態[待退訂]，付款狀態不可為[未付款]、[已付訂金]、[已付全額]、[已棄單，已退款]。'] , 400);
                    }
                }
                else if($validated['booking_status'] === '已退訂'){
                    if($validated['payment_status'] !== '已棄單，已退款' && $validated['payment_status'] !== '已棄單，免退款' && $validated['payment_status'] !== '已棄單，待退款'){
                        return response()->json(['error' => '預定狀態[已退訂]，付款狀態不可為[未付款]、[已付訂金]、[已付全額]。'] , 400);
                    }
                }
            }
            else if($data_before['document']['booking_status'] === "已退訂"){
                if($validated['booking_status'] !== "已退訂"){
                    return response()->json(['error' => '預定狀態只可以維持[已退訂]，不可更改。'], 400);
                }
                if($validated['payment_status'] !== "已棄單，已退款" && $validated['payment_status'] !== "已棄單，免退款"){
                    return response()->json(['error' => '預定狀態[已退訂]，付款狀態只可為[已棄單，已退款]、[已棄單，免退款]。'] , 400);
                }
            }
        }

        //針對付款狀態做判斷
        if($data_before['document']['payment_status'] === "已棄單，待退款"){
            if($validated['payment_status'] !== "已棄單，待退款" && $validated['payment_status'] !== "已棄單，已退款"){
                return response()->json(['error' => "付款狀態只可更改為[已棄單，待退款]或[已棄單，已退款]"], 400);
            }
        }
        if($data_before['document']['payment_status'] === "已棄單，已退款" && $validated['payment_status'] !== "已棄單，已退款"){
            return response()->json(['error' => "付款狀態只可更改為[已棄單，待退款]或[已棄單，已退款]"], 400);
        }
        if($data_before['document']['payment_status'] === "已棄單，免退款" && $validated['payment_status'] !== "已棄單，免退款"){
            return response()->json(['error' => "付款狀態只可維持[已棄單，免退款]"], 400);
        }

        if($data_before['document']['payment_status'] === "已棄單，待退款" && $validated['payment_status'] === "已棄單，已退款"){
            $validated['deleted_at'] = date('Y-m-d H:i:s');
        }
        else if($data_before['document']['payment_status'] === "未付款" &&  $validated['payment_status'] === "已棄單，免退款"){
            $validated['deleted_at'] = date('Y-m-d H:i:s');
        }
        $result = $this->requestService->update_one('cus_delete_components', $validated);
        return $result;
    }
}
