<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\RequestStatesService;
use App\Services\RequestCostService;


use App\Services\ItineraryGroupService;
use Validator;

class ItineraryGroupController extends Controller
{
    private $requestService;
    private $requestStatesService;
    private $requestCostService;


    public function __construct(RequestPService $requestService, RequestStatesService $requestStatesService, RequestCostService $requestCostService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->requestStatesService = $requestStatesService;
        $this->requestCostService = $requestCostService;


        $this->rule = [
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
            'itinerary_group_cost' => 'required|numeric',
            'itinerary_group_price' => 'required|numeric',
            'include_description' => 'nullable|string|max:150',
            'exclude_description' => 'nullable|string|max:150',
        ];
        $this->edit_rule = [
            '_id'=>'string|max:24', //required
            'owned_by'=>'required|integer',
            'order_id' => 'required|string',
            'name' => 'required|string|max:30',
            'summary' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
            'total_day' => 'required|integer|max:7',
            'areas' => 'nullable|array',
            'people_threshold' => 'required|integer|min:1',
            'people_full' => 'required|integer|max:100',
            'sub_categories' => 'nullable|array',
            'itinerary_content' => 'required|array|min:1',
            'guides' => 'present|array',
            'transportations' => 'present|array',
            'misc' => 'present|array',
            'accounting' => 'required|array',
            'include_description' => 'nullable|string|max:150',
            'exclude_description' => 'nullable|string|max:150',
            'itinerary_group_cost' => 'required|numeric',
            'itinerary_group_price' => 'required|numeric',
            'itinerary_group_note' => 'string|max:150'
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

    public function add(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;


        // TODO(US-407) 需要將所有傳入時間(string)改成時間(date)傳入


        // TODO(US-390) 檢查行程內容
/*         try{
            $is = new ItineraryGroupService($validated);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } */

        // 建立前，判斷行程代碼是否重複 : 同公司不存在相同行程代碼，為空則不理
        if(array_key_exists('code', $validated)){
            $filter["code"] = $validated['code'];
            $filter["owned_by"] = $validated['owned_by'];
            $validated['operator_note'] = null;

            $result_code = $this->requestService->aggregate_search('itinerary_group', null, $filter, $page=0);
            $result_code_data = json_decode($result_code->getContent(), true);
            return $result_code_data;

            if($result_code_data["count"] > 0) return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
        }else $validated['code'] = null;


        // 建立團行程，並回傳 團行程id
        $result = $this->requestService->insert_one('itinerary_group', $validated); // 回傳是否建立成功
        $result_data = json_decode($result->getContent(), true);

        // 找出團行程的 order_id，去修改 order itinerary_group_id、cus_group_code
        $itinerary_group = $this->requestService->get_one('itinerary_group', $result_data['inserted_id']);
        $itinerary_group_data = json_decode($itinerary_group->getContent(), true);
        $order = $this->requestService->get_one('cus_orders', $itinerary_group_data["order_id"]);
        $order_data = json_decode($order->getContent(), true);

        //處理created_at:2022-03-09T17:52:30 -> 20220309_
        $created_at_date = substr($order_data["created_at"], 0, 10);
        $created_at_time = substr($order_data["created_at"], 11);
        $created_at_date = preg_replace('/-/', "", $created_at_date);
        $created_at_time = preg_replace('/:/', "", $created_at_time);


        $fixed["itinerary_group_id"] = $itinerary_group_data['_id'];


        //CUS_"行程代碼"_"旅行社員工id"_"客製團訂單日期"_"客製團訂單時間"_"行程天數"_"第幾團"
        if(array_key_exists('code', $itinerary_group_data)){
            $fixed["cus_group_code"] = "CUS_".$itinerary_group_data['code']."_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
        }else if(!array_key_exists('code', $itinerary_group_data) && $validated['code'] !== null){
            $fixed["cus_group_code"] = "CUS_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
        }

        $fixed["_id"] = $order_data["_id"];
        $result = $this->requestService->update_one('cus_orders', $fixed);
        return $result;

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
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正 -> 關聯 get_id
        if($user_company_id !== $validated['owned_by']){
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
            if(strtotime($validated['travel_end']) - strtotime($validated['travel_start']) <= 0){
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
                    $amount_validated["total"] += $validated['transportations'][$i]['sum'];
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
            $validated['operator_note']= null;
            if(!array_key_exists('itinerary_group_note', $validated)){
                $validated['itinerary_group_note'] = null;
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
                $fixed["cus_group_code"] = "CUS_".$itinerary_group_data['code']."_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
            }else if(!array_key_exists('code', $itinerary_group_data) && $validated['code'] !== null){
                $fixed["cus_group_code"] = "CUS_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
            }

            $fixed["_id"] = $order_data["_id"];
            $fixed["itinerary_group_id"] = $result_data['inserted_id'];
            $fixed["amount"] = $itinerary_group_data['itinerary_group_price'];

            $result = $this->requestService->update_one('cus_orders', $fixed);
            return $result;

        }elseif(array_key_exists('_id', $validated)){
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

            if(strtotime($validated['travel_end']) - strtotime($validated['travel_start']) <= 0){
                return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'], 400);
            }

            $amount_validated["total"] = 0;

            if(array_key_exists('itinerary_content', $validated)){
                for($i = 0; $i < count($validated['itinerary_content']); $i++){
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
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['guides'][$i]['type'], $validated['guides'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }
            if(array_key_exists('transportations', $validated)){
                for($i = 0; $i < count($validated['transportations']); $i++){
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['transportations'][$i]['type'], $validated['transportations'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }
            if(array_key_exists('misc', $validated)){
                for($i = 0; $i < count($validated['misc']); $i++){
                    $amount = $this->requestCostService->validated_cost($cus_orders_data, $validated['misc'][$i]['type'], $validated['misc'][$i]);
                    $amount_validated['total'] += $amount['total'];
                }
            }


            if($amount_validated['total'] !== $validated['itinerary_group_cost']){
                return response()->json(['error' => "所有元件加總不等於總直成本(itinerary_group_cost)"], 400);
            }

            $this->requestService->update('itinerary_group', $validated);

            $fixed["_id"] = $validated["order_id"];
            $fixed["itinerary_group_id"] = $validated['_id'];
            $fixed["amount"] = $validated['itinerary_group_price'];

            $result = $this->requestService->update_one('cus_orders', $fixed);
            return $result;
        }
    }

    // filter: 區域, 行程名稱, 子類別, 天數起訖, 成團人數, 滿團人數, 頁數
    // name, areas, sub_categories, total_day, people_threshold, people_full, page

    // project: ID, 名稱, 子類別, 行程天數, 成團人數, 建立日期

    // TODO 尚未修改
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

        // Handle itinerary sub_categories
        if (array_key_exists('sub_categories', $filter)) {
            $category = $filter['sub_categories'];
            $filter['sub_categories'] = array('$elemMatch' => array('$in' => $category));
        }

        // Handle itinerary areas
        // if (array_key_exists('areas', $filter)) {
        //     $areas = $filter['areas'];
        //     $filter['areas'] = array('$elemMatch' =>array('$in' => $areas));
        // }

        // Handle itinerary totoal_day range query
        if (array_key_exists('total_day_range', $filter)){
            if (array_key_exists('total_day_min', $filter['total_day_range']) && array_key_exists('total_day_max', $filter['total_day_range'])){
                $filter['total_day'] = array('$gte' => $filter['total_day_range']['total_day_min'], '$lte' => $filter['total_day_range']['total_day_max']);
            }
            elseif (array_key_exists('total_day_min', $filter['total_day_range']) && !array_key_exists('total_day_max', $filter['total_day_range'])){
                $filter['total_day'] = array('$gte' => $filter['total_day_range']['total_day_min']);
            }
            elseif (!array_key_exists('total_day_min', $filter['total_day_range']) && array_key_exists('total_day_max', $filter['total_day_range'])){
                $filter['total_day'] = array('$lte' => $filter['total_day_range']['total_day_max']);
            }
        }
        unset($filter['total_day_range']);

        // Handle itinerary area query
        if (array_key_exists('areas', $filter)) {
            $areas = $filter['areas'];
            $filter['areas'] = array('$in' => $areas);
        }


        $company_type = auth()->payload()->get('company_type');
        $company_id = auth()->payload()->get('company_id');
        if ($company_type == 1){
        }elseif ($company_type == 2){
            $query_private = false;
            $filter['owned_by'] = auth()->user()->company_id;
            // $filter['is_display'] = true;
        }else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        $projection = array(
                // "_id" => 1,
                // "name" => 1,
                // "sub_categories" => 1,
                // "total_day" => 1,
                // "people_threshold" => 1,
                // "accounting" => 1,
                // "imgs" => 1,
                // "areas" => 1,
                // "created_at" => 1
            );
        $result = $this->requestService->aggregate_facet('itineraries', $projection, $company_id, $filter, $page, $query_private);
        return $result;
    }

    public function get_by_id($id)
    { //傳入訂單id
        // 1-1 使用者公司必須是旅行社
        $user_company_id = auth()->user()->company_id;
        $contact_name = auth()->user()->contact_name;
        $company_data = Company::find($user_company_id);
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
        if($user_company_id !== $cus_order_data['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        if($cus_order_data['itinerary_group_id']){ //old
            $itinerary_group = $this->requestService->get_one('itinerary_group', $cus_order_data['itinerary_group_id']);
            $itinerary_group_data =  json_decode($itinerary_group->content(), true);
            // 如果已經沒有該團行程 需要額外處理
            if(array_key_exists('count', $itinerary_group_data) && $itinerary_group_data['count'] === 0){
                return response()->json(['error' => '訂單中團行程id可能已過期(團行程刪除)。'], 400);
            }
            return $itinerary_group_data;
        }elseif(!$cus_order_data['itinerary_group_id']){ //new

            // TODO 這部分感覺可以優化
            $itinerary_group_data_new['order_id'] = $cus_order_data['_id'];
            $itinerary_group_data_new['name'] = "";
            $itinerary_group_data_new['summary'] = "";
            $itinerary_group_data_new['code'] = "";
            $itinerary_group_data_new['travel_start'] = "";
            $itinerary_group_data_new['travel_end'] = "";
            $itinerary_group_data_new['total_day'] = 1;
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
            $itinerary_group_data_new['owned_by'] = $user_company_id;
            return $itinerary_group_data_new;
        }else{
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
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($user_company_id !== $data_before['owned_by']){
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
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        if($user_company_id !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO 團行程[名稱]、[代碼]必須和行程不同
        //$filter['company_id'] = $user_company_id;
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
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($user_company_id !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO Validation 基本驗證 所有和金額有關必須不小於0
        if($validated['deposit'] < 0 && $validated['balance'] < 0 && $validated['amount'] < 0){
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
        if($itinerary_group_order_data['document']['order_status'] !== "已成團" && $itinerary_group_order_data['document']['order_status'] !== "棄單"){
            return response()->json(['error' => "訂單狀態不是[已成團]或[棄單]不可更改付款狀態"], 400);
        }

        // 修改付款狀態
        if(array_key_exists("date", $validated) && array_key_exists("sort", $validated)){
            if($validated["sort"]<=0){
                return response()->json(['error' => "sort 必須大於0。"]);
            }
            $find_day = floor((strtotime($validated["date"]) - strtotime($validated['travel_start'])) / (60*60*24)); //將 date 做轉換成第幾天
            $find_sort = $validated["sort"]-1; // sort比原來少1
            if(array_key_exists("type", $validated)){
                if($validated["type"] === "attractions" || $validated["type"] === "accomendations" || $validated["type"] === "activities" || $validated["type"] === "restaurants"){
                    $find_type = 'itinerary_content';
                    $find_name = $find_type.".".$find_day.".components.".$find_sort.".";
                    $find_name_no_dot = $find_type.".".$find_day.".components.".$find_sort;
                    if($itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort] === null){
                        return response()->json(['error' => "位於[景點]或[住宿]或[活動]或[餐廳]元件中，找不到該筆元件資訊"], 400);
                    }
                }else if($validated["type"] === "transportations" || $validated["type"] === "guides"){
                    $find_type =$validated["type"];
                    $find_name = $find_type.".".$find_sort.".";
                    $find_name_no_dot = $find_type.".".$find_sort;
                    if($itinerary_group_past_data[$find_type][$find_sort] === null){
                        return response()->json(['error' => "位於[交通工具]或[導遊]元件中，找不到該筆元件資訊"], 400);
                    }
                }
            }else{
                return response()->json(['error' => '沒有傳回修改項目名稱(type)'], 400);
            }
        }else{
            return response()->json(['error' => 'date, sort are not defined.'], 400);
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
            }elseif($validated['pay_deposit'] === 'false'){
                if(array_key_exists("deposit", $validated) && $validated['deposit'] > 0){
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
        }elseif($validated['payment_status'] === "已付全額"){
            $fixed[$find_name.'actual_payment'] = $validated['amount'];
        }

        // 抓到更新欄位
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
            if($result_booking !== 1) return $result_booking;
            $result_payment = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]);
            if($result_payment !== 1) return $result_payment;

        }else if($find_type === "transportations" || $find_type === "guides"){
            $result_booking = $this->requestStatesService->booking_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result_booking !== 1) return $result_booking;
            $result_payment = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result_payment !== 1) return $result_payment;
        }

        // 確定沒錯後存入團行程中
        $update = $this->requestService->update_one('itinerary_group', $fixed);


        // 當狀態須加上刪除判斷 如果待退已退則刪除該obj放入刪除DB中
        if($validated['booking_status'] === "未預訂" || $validated['booking_status']=== "已預訂"){
            return response()->json(['success' => "預訂狀態為[未預訂]或[已預訂]，更新成功!"], 200);
        }elseif($validated['booking_status'] === "待退訂"){

            // 取得存後資料
            $operator_data = $this->requestService->get_one('itinerary_group', $validated['_id']);
            $operator_data = json_decode($operator_data->getContent(), true);

            // 判斷該筆資料type，欲處理待退訂項目
            if($find_type === "itinerary_content"){
                $to_deleted = $operator_data[$find_type][$find_day]['components'][$find_sort];
            }elseif($find_type === "transportations" || $find_type === "guides"){
                $to_deleted = $operator_data[$find_type][$find_sort];
            }

            // 取得 客製化團 人數
            $find_people["_id"] = $operator_data['order_id'];
            $cus_orders = $this->requestService->get_one('cus_orders', $find_people['_id']);
            $cus_orders_data = json_decode($cus_orders->getContent(), true);
            $to_deleted["total_people"] = $cus_orders_data['total_people'];

            $to_deleted['to_be_deleted'] = date('Y-m-d H:i:s');
            $to_deleted['deleted_at'] = null;
            $to_deleted['deleted_reason'] = null;
            $to_deleted['order_id'] = $operator_data['order_id'];
            $to_deleted['itinerary_group_id'] = $operator_data['_id'];
            $to_deleted['component_id'] = $validated['_id'];
            unset($to_deleted['sort']);

            // 加入刪除資料庫中
            $deleted_result = $this->requestService->insert_one('cus_delete_components', $to_deleted);

            // 修改團行程成本(需要扣掉的)
            $delete_component_data = $this->requestCostService->after_delete_component_cost($itinerary_group_past_data["accounting"],$itinerary_group_past_data['itinerary_group_cost'], $to_deleted);

            $result = $this->requestService->update_one('itinerary_group', $delete_component_data);

            // 刪除團行程該元件
            $to_deleted_itinerary['_id'] = $validated['_id'];
            $to_deleted_itinerary[$find_name_no_dot] = null;
            $this->requestService->delete_field('itinerary_group', $to_deleted_itinerary);
            return response()->json(["已成功刪除此元件、更新成本，請至團行程編輯中修改定價!"], 200);

        }
    }

    public function get_delete_items($id)
    { // 過濾出該[團行程]全部刪除內容

        // 1-1 使用者公司必須是旅行社
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data = $this->requestService->find_one('itinerary_group', $id, null, null);
        if(!$data){
            return response()->json(['error' => '沒有這筆團行程資料。'], 400);
        }
        if($user_company_id !== $data['document']['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $filter['itinerary_group_id'] = $id;
        $result_code = $this->requestService->aggregate_search('cus_delete_components', null, $filter, $page=0);
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
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data_before = $this->requestService->find_one('cus_delete_components', $validated['_id'], null, null);
        $data_of_itinerary_group_before = $this->requestService->find_one('itinerary_group', $data_before['document']['itinerary_group_id'], null, null);
        if($user_company_id !== $data_of_itinerary_group_before['document']['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // 直接針對待退已退做判斷
        if(array_key_exists("payment_status", $validated) && array_key_exists("booking_status", $validated)){
            if($data_before['document']['booking_status'] === "待退訂"){
                if($validated['booking_status'] !== "待退訂" && $validated['booking_status'] !== "已退訂"){
                    return response()->json(['error' => '預定狀態[待退訂]只可以維持[待退訂]或是改成[已退訂]。'] , 400);
                }
                if($validated['booking_status'] === '待退訂'){
                    if($validated['payment_status'] !== '已棄單，待退款' && $validated['payment_status'] !== '已棄單，免退款'){
                        return response()->json(['error' => '預定狀態[待退訂]，付款狀態不可為[未付款]、[已付訂金]、[已付全額]、[已棄單，已退款]。'] , 400);
                    }
                }elseif($validated['booking_status'] === '已退訂'){
                    if($validated['payment_status'] !== '已棄單，已退款' && $validated['payment_status'] !== '已棄單，免退款'){
                        return response()->json(['error' => '預定狀態[待退訂]，付款狀態不可為[未付款]、[已付訂金]、[已付全額]、[已棄單，已退款]。'] , 400);
                    }
                }
            }elseif($data_before['document']['booking_status'] === "已退訂"){
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
        }elseif($data_before['document']['payment_status'] === "未付款" &&  $validated['payment_status'] === "已棄單，免退款"){
            $validated['deleted_at'] = date('Y-m-d H:i:s');
        }
        $result = $this->requestService->update_one('cus_delete_components', $validated);
        return $result;
    }
}
