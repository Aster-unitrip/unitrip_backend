<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\OrderService;
use App\Rules\Boolean;

use Validator;




class OrderController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestPService, OrderService $orderService)
    {
        $this->middleware('auth');
        $this->requestService = $requestPService;
        $this->orderService = $orderService;


        // 前端條件
        $this->rule = [
            'representative' => 'required|string|max:30',
            'company' => 'nullable|string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'currency' => 'required|string|max:10',
            'languages' => 'required|array',
            'adult_number' => 'required|integer',
            'child_number' => 'required|integer',
            'baby_number' => 'required|integer',
            'source' => 'required|string',
            'needs' => 'nullable|string',
            'company_note' => 'nullable|string',
            'estimated_travel_start' => 'required|date',
            'estimated_travel_end' => 'required|date',
            'total_day' =>'required|integer|min:1',
            'representative_company_tax_id' => 'nullable|string',
            'budget' => 'required|array',
        ];
        $this->edit_rule = [
            '_id'=>'required|string|max:24',
            'order_status' => 'required|string',
            'out_status' => 'required|string',
            'payment_status' => 'required|string',
            'cus_group_code' => 'nullable|string',
            'representative' => 'required|string|max:30',
            'company' => 'nullable|string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'currency' => 'required|string|max:10',
            'languages' => 'required|array',
            'estimated_travel_start' => 'required|date',
            'estimated_travel_end' => 'required|date',
            'total_day' =>'required|integer|min:1',
            'adult_number' => 'required|integer',
            'child_number' => 'required|integer',
            'baby_number' => 'required|integer',
            'source' => 'required|string',
            'needs' => 'nullable|string',
            'company_note' => 'nullable|string',
            'budget' => 'required|array',
            'representative_company_tax_id' => 'nullable|string',

        ];
        $this->operator_rule = [
            '_id'=>'required|string|max:24',
            'pay_deposit' => ['required', new Boolean],
            'payment_status' => 'required|string',
            'deposit' => 'required|numeric',
            'balance' => 'required|numeric',
            'amount' => 'required|numeric'
        ];
    }


    public function add(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        //預設
        $user_name = auth()->user()->contact_name;
        $validated['owned_by'] = auth()->user()->company_id;
        $validated['owned_by_id'] = auth()->user()->id;
        $now_date = date('Ymd');
        $now_time = date("His" , mktime(date('H')+8, date('i'), date('s')));

        if(array_key_exists("budget", $validated)){
            if(!array_key_exists("budgetMin", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人預算最小值。]"]);
            }
            if(!array_key_exists("budgetMax", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人預算最大值。]"]);
            }
            if(!array_key_exists("budgetMinPerDay", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人每日預算最小值。]"]);
            }
            if(!array_key_exists("budgetMaxPerDay", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人每日預算最大值。]"]);
            }
        }

        //找user公司名稱
        $company_data = Company::find($validated['owned_by']);
        $validated['user_company_name'] = $company_data['title'];
        $validated['order_status'] = "收到需求單";
        $validated['payment_status'] = "未付款";
        $validated['out_status'] = "未出團";
        $validated['order_number'] = "CUS_".$now_date."_".$now_time;
        $validated['last_updated_on'] = $user_name;
        $validated['user_name'] = $user_name;
        $validated['order_record'] = array();
        $order_record_add_order_status = array(
            "event" =>  $validated['order_status'],
            "date" => date('Y-m-d')." ".date("H:i:s"),
            "modified_by" => $user_name
        );
        array_push($validated['order_record'], $order_record_add_order_status);

        $validated['pay_deposit'] = 'false';
        $validated['deposit'] = 0;
        $validated['balance'] = 0;
        $validated['amount'] = 0;
        $validated['actual_payment'] = 0; //旅客實際支付金額
        $validated['cancel_at'] = null;
        $validated['cus_group_code'] = null;
        $validated['operator_note'] = null;
        $validated['group_status'] = "未成團";

        if(!array_key_exists('adult_number', $validated)) $validated['adult_number'] = 0;
        if(!array_key_exists('child_number', $validated)) $validated['child_number'] = 0;
        if(!array_key_exists('baby_number', $validated)) $validated['baby_number'] = 0;
        $validated['total_people'] = $validated['adult_number'] + $validated['child_number'] + $validated['baby_number'];

        if(!array_key_exists('company', $validated)) $validated['company'] = null;


        $validated['itinerary_group_id'] = null; //團行程一開始沒有(versions)
        $validated['estimated_travel_start'] = $validated['estimated_travel_start']."T00:00:00.000+08:00";
        $validated['estimated_travel_end'] = $validated['estimated_travel_end']."T23:59:59.000+08:00";

        $cus_orders = $this->requestService->insert_one('cus_orders', $validated);
        return $cus_orders;

    }

    public function edit(Request $request)
    {
        // 2. 先驗證前端傳回資料
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        $user_name = auth()->user()->contact_name;

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 3.用id抓編輯前資料及前端給的資料，之後需要比較(4.5.6.)
        $id = $validated['_id'];
        $data_before = $this->requestService->find_one('cus_orders', $id, null, null);
        if($data_before===false){
            return response()->json(['error' => '此[_id]搜尋不到這筆訂單。'], 400);
        }

        $data_before = $data_before['document'];


        // 非旅行社及該旅行社人員不可修改訂單
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($owned_by !== $data_before['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // budget
        if(array_key_exists("budget", $validated)){
            if(!array_key_exists("budgetMin", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人預算最小值。]"]);
            }
            if(!array_key_exists("budgetMax", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人預算最大值。]"]);
            }
            if(!array_key_exists("budgetMinPerDay", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人每日預算最小值。]"]);
            }
            if(!array_key_exists("budgetMaxPerDay", $validated['budget'])){
                return response()->json(['error' => "請輸入[訂單 - 行程每人每日預算最大值。]"]);
            }
        }

        // TODO381 6. 出團狀態 需寫判斷
        if(array_key_exists('out_status',$validated) && $data_before['out_status'] !== $validated['out_status']){
            //客製化付款狀態: 0.未出團 ->1 / 1.出團中 ->2 3 / 2.已出團，未結團 ->3 / 3.已結團 -> X
            switch($data_before['out_status']){
                case "未出團":
                    if($validated['out_status'] !== "出團中"){
                        return response()->json(['error' => "[出團狀態]只可改到[出團中]"], 400);
                    }
                    break;
                case "出團中":
                    if($validated['out_status'] !== "已出團，未結團" && $validated['out_status'] !== "已結團"){
                        return response()->json(['error' => "[出團狀態]只可改到[已出團，未結團]、[已結團]"], 400);
                    }
                    break;
                case "已出團，未結團":
                    if($validated['out_status'] !== "已結團"){
                        return response()->json(['error' => "[出團狀態]只可改到[已結團]"], 400);
                    }
                    break;
            }
        }

        // TODO381 5. 付款狀態 需寫判斷
        if(array_key_exists('payment_status',$validated) && $data_before['payment_status'] !== $validated['payment_status']){
            //客製化付款狀態: 0.未付款 -> 1 2 5 / 1.已付訂金 ->2 3 / 2.已付全額 -> 3 / 3.已棄單，待退款 -> 4 / 4.已棄單，已退款 -> X / 5.已棄單，免退款 -> X

            switch($data_before['payment_status']){
                case "未付款":
                    if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                        return response()->json(['error' => "[付款狀態]只可改到狀態[已付訂金]、[已付全額]、[已棄單，免退款]"], 400);
                    }
                    break;
                case "已付訂金":
                    if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "[付款狀態]只可改到狀態[已付全額]、[已棄單，待退款]"], 400);
                    }
                    break;
                case "已付全額":
                    if($validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "[付款狀態]只可改到狀態[已棄單，待退款]"], 400);
                    }
                    break;
                case "已棄單，待退款":
                    if($validated['payment_status'] !== "已棄單，已退款"){
                        return response()->json(['error' => "[付款狀態]只可改到狀態[已棄單，已退款]"], 400);
                    }
                    break;
            }
        }

        // 4. 訂單狀態->參團號碼 需寫判斷
        // 寫入資料 $validated / 比較資料(在資料庫) $data_before
        if(array_key_exists('order_status',$validated) && $data_before['order_status'] !== $validated['order_status']){
            //客製化訂單狀態: 0.收到需求單 -> 1 4 / 1.已規劃行程&詢價 ->2 4 / 2.已回覆，待旅客確認 ->1 3 4 / 3.已成團 -> 4 / 4.棄單 -> X
            switch($data_before['order_status']){
                case "收到需求單":
                    if($validated['order_status'] !== "已規劃行程&詢價" && $validated['order_status'] !== "棄單"){
                        return response()->json(['error' => "[訂單狀態]只可改到[已規劃行程&詢價]、[棄單]"], 400);
                    }
                    break;
                case "已規劃行程&詢價":
                    if($validated['order_status'] !== "已回覆，待旅客確認" && $validated['order_status'] !== "棄單"){
                        return response()->json(['error' => "[訂單狀態]只可改到[已回覆，待旅客確認]、[棄單]"], 400);
                    }
                    break;
                case "已回覆，待旅客確認":
                    if($validated['order_status'] !== "已規劃行程&詢價" && $validated['order_status'] !== "棄單" && $validated['order_status'] !== "已成團"){
                        return response()->json(['error' => "[訂單狀態]只可改到[已規劃行程&詢價]、[已成團]、[棄單]"], 400);
                    }
                    break;
                case "已成團":
                    if($validated['order_status'] !== "棄單"){
                        return response()->json(['error' => "[訂單狀態]只可改到[棄單]"], 400);
                    }
                    break;
            }

            // 預定狀態關聯付款狀態
            if($validated['order_status'] === "收到需求單" || $validated['order_status'] === "已規劃行程&詢價" || $validated['order_status'] === "已回覆，待旅客確認"){
                if($validated['payment_status'] !== "未付款"){
                    return response()->json(['error' => "[訂單狀態]為[收到需求單]或[已規劃行程&詢價]或[已回覆，待旅客確認]時，[付款狀態]只可為[未付款]。"]);
                }
                if($validated['out_status'] !== "未出團"){
                    return response()->json(['error' => "[訂單狀態]為[收到需求單]或[已規劃行程&詢價]或[已回覆，待旅客確認]時，[出團狀態]只可為[未出團]。"]);
                }
            }
            else if($validated['order_status'] === "已成團"){
                if($validated['payment_status'] !== "未付款" && $validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額"){
                    return response()->json(['error' => "[訂單狀態]為[已成團]時，[付款狀態]只可為[未付款]或[已付訂金]或[已付全額]。"]);
                }
                if($validated['payment_status'] !== "已付全額" && $validated['out_status'] === "已結團"){
                    return response()->json(['error' => "[訂單狀態]為[已成團]且[出團狀態]為[已結團]，[付款狀態]必須為[已付全額]。"]);
                }
            }
            else if($validated['order_status'] === "棄單"){
                if($validated['payment_status'] !== "已棄單，待退款" && $validated['payment_status'] !== "已棄單，已退款" && $validated['payment_status'] !== "已棄單，免退款"){
                    return response()->json(['error' => "[訂單狀態]為[棄單]時，[付款狀態]只可為[已棄單，待退款]或[已棄單，已退款]或[已棄單，免退款]。"]);
                }
                if($validated['out_status'] !== "未出團"){
                    return response()->json(['error' => "[訂單狀態]為[棄單]時，[出團狀態]只可為[未出團]。"]);
                }
            }

            //存入 order_record
            $order_record_add_order_status = array(
                "event" =>  $validated['order_status'],
                "date" => date('Y-m-d')." ".date("H:i:s"),
                "modified_by" => $user_name
            );
            $validated['order_record'] = $data_before['order_record'];
            array_push($validated['order_record'], $order_record_add_order_status);
        }

        //成團狀態
        if($validated['order_status'] === "已成團") $validated['group_status'] = "成團";
        if($validated['order_status'] === "棄單"){
            $validated['group_status'] = "未成團";
            $validated['cancel_at'] = date('Y-m-d H:i:s');
        }
        $validated['last_updated_on'] = $user_name;

        // 參團編號需擋重複
        if(array_key_exists('cus_group_code', $validated)){
            // 需檔自己公司
            $find_one['owned_by'] = $owned_by;
            $find_one['cus_group_code'] = $validated['cus_group_code'];
            $cus_orders_past = $this->requestService->aggregate_search('cus_orders', null, $find_one, $page=0);
            $cus_orders_past_data = json_decode($cus_orders_past->getContent(), true);
            //如果只有一筆，判斷是否為重複
            if($cus_orders_past_data['count'] > 1 && $validated['cus_group_code'] !== null){
                return response()->json(['error' => "同公司不可重複相同參團編號"], 400);
            }
        }

        $validated['estimated_travel_start'] = $validated['estimated_travel_start']."T00:00:00.000+08:00";
        $validated['estimated_travel_end'] = $validated['estimated_travel_end']."T23:59:59.000+08:00";

        //總人數 = 各項人數相加
        $validated['total_people'] = $validated['adult_number'] + $validated['child_number'] + $validated['baby_number'];
        $cus_orders = $this->requestService->update_one('cus_orders', $validated);
        return $cus_orders;
    }


    // filter: 訂單編號, 參團編號, 旅客代表人姓名, 來源, 訂購期間(ordertime_start、ordertime_end), 行程期間, 負責人, 出團狀態, 付款狀態, 頁數

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

        //擋下供應商/其他公司的id
        $company_id = auth()->payload()->get('company_id');
        $filter['owned_by'] = auth()->user()->company_id;

        // 找該 company 的 types
        $company_data = Company::find($company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        //訂購期間為 order_start<= created_at <=order_end
        if(array_key_exists('order_start', $filter) && array_key_exists('order_end', $filter)){
            if(strtotime($filter['order_end']) - strtotime($filter['order_start']) >= 0){
                $filter['created_at'] = array('$gte' => $filter['order_start']."T00:00:00.000+00:00"
                , '$lte' => $filter['order_end']."T23:59:59.000+00:00");
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

        if(array_key_exists("estimated_travel_start", $filter) && array_key_exists('estimated_travel_end', $filter)){
            if(strtotime($filter['estimated_travel_end']) - strtotime($filter['estimated_travel_start']) >= 0){
                $filter['estimated_travel_start'] = array('$gte' => $filter['estimated_travel_start']."T00:00:00.000+08:00"
                , '$lte' => $filter['estimated_travel_start']."T23:59:59.000+08:00");
                $filter['estimated_travel_end'] = array('$gte' => $filter['estimated_travel_end']."T00:00:00.000+08:00"
                , '$lte' => $filter['estimated_travel_end']."T23:59:59.000+08:00");
            }else{
                return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'], 400);
            }
        }

        //sort by [created_at]、[travel_start]
        if(array_key_exists('sort', $filter)){
            // 轉換sort
            $filter["searchSort"] = $this->orderService->change_search_sort($filter['sort']);
            unset($filter['sort']);
        }else{
            $filter["searchSort"]["created_at"] = -1;
        }

        //[訂單編號]、[代表人]、[參團編號]使用模糊搜尋
        if(array_key_exists('representative', $filter)){
            $filter['representative'] = array('$regex' => trim($filter['representative']));
        }
        if(array_key_exists('order_number', $filter)){
            $filter['order_number'] = array('$regex' => trim($filter['order_number']));
        }
        if(array_key_exists('cus_group_code', $filter)){
            $filter['cus_group_code'] = array('$regex' => trim($filter['cus_group_code']));
        }

        $result = $this->requestService->aggregate_search('cus_orders', null, $filter, $page);
        return $result;

    }

    public function get_by_id($id)
    {
        // 非旅行社及該旅行社人員不可修改訂單
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data_before = $this->requestService->find_one('cus_orders', $id, null, null);
        if($data_before===false){
            return response()->json(['error' => '輸入id搜尋不到訂單。'], 400);
        }

        $data_before = $data_before['document'];

        if($owned_by !== $data_before['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $result = $this->requestService->get_one('cus_orders', $id);
        $content = json_decode($result->content(), true);
        return $content;
    }

    public function operator(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->operator_rule);
        $validated = $validator->validated();

        // 非旅行社及該旅行社人員不可修改訂單
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        //將該筆 order 調出
        $cus_orders_past = $this->requestService->find_one('cus_orders', null, '_id', $validated['_id']);
        if(!$cus_orders_past) return response()->json(['error' => '訂單中沒有這個id'], 400);
        $cus_orders_past = $cus_orders_past['document'];

        //判斷是否為該公司
        if($owned_by !== $cus_orders_past['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO381 如有要改付款狀態 必須在訂單狀態為 已成團才可以修改

        if($cus_orders_past['order_status'] === "已成團"){
            if(array_key_exists('payment_status', $validated)){
                if($cus_orders_past['payment_status'] !== $validated['payment_status']){
                    switch($cus_orders_past['payment_status']){
                        case "未付款":
                            if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                                return response()->json(['error' => "[付款狀態]只可改到[已付訂金]、[已付全額]、[已棄單，免退款]"], 400);
                            }
                            break;
                        case "已付訂金":
                            if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                                return response()->json(['error' => "[付款狀態]只可改到狀態[已付全額]、[已棄單，待退款]"], 400);
                            }
                            break;
                        case "已付全額":
                            if($validated['payment_status'] !== "已棄單，待退款"){
                                return response()->json(['error' => "[付款狀態]只可改到狀態[已棄單，待退款]"], 400);
                            }
                            break;
                        case "已棄單，待退款":
                            if($validated['payment_status'] !== "已棄單，已退款"){
                                return response()->json(['error' => "[付款狀態]只可改到狀態[已棄單，已退款]"], 400);
                            }
                            break;
                    }
                }
            }
            else{
                return response()->json(['error' =>'沒有[付款狀態]欄位', 400]);
            }

            // TODO381 是否付訂金此欄必須為true後才可以更改
            // 當此次輸入[是否需預付訂金]為[否] 或是 未輸入資料庫[是否需預付訂金]為[否]，若[付款狀態]不可以為[已付訂金]
            if((array_key_exists('pay_deposit', $validated) && $validated['pay_deposit'] === 'false') && $validated['payment_status'] === "已付訂金"){
                return response()->json(['error' => "當[是否需預付訂金]為[否]時，[付款狀態]不可以為[已付訂金]"], 400);
            }
        }
        else{
            return response()->json(['error' => '[付款狀態]必須是[已成團]，才可以更改[付款狀態]'], 400);
        }

        //TODO未完成 將所有元件驗算
        if($validated['payment_status'] === "已付訂金"){
            $validated['actual_payment'] = $validated['amount'] - $validated['deposit'];
        }
        elseif($validated['payment_status'] === "已付全額"){
            $validated['actual_payment'] = $validated['amount'];
        }

        $cus_orders = $this->requestService->update_one('cus_orders', $validated);
        return $cus_orders;
    }
}
