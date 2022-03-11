<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\OrderService;

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
            'order_passenger' => 'required|string|max:30',
            'company' => 'nullable|string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'currency' => 'required|string|max:10',
            'languages' => 'required|array',
            'budget_min' => 'required|numeric',
            'budget_max' => 'required|numeric',
            'adult_num' => 'required|integer',
            'child_num' => 'required|integer',
            'baby_num' => 'required|integer',
            'source' => 'required|string',
            'needs' => 'nullable|string',
            'note' => 'nullable|string',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
        ];
        $this->edit_rule = [
            '_id'=>'required|string|max:24',
            'order_status' => 'required|string',
            'out_status' => 'required|string',
            'payment_status' => 'required|string',
            'cus_group_code' => 'string',
            'order_passenger' => 'required|string|max:30',
            'company' => 'string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'currency' => 'required|string|max:10',
            'languages' => 'required|array',
            'budget_min' => 'required|numeric',
            'budget_max' => 'required|numeric',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
            'adult_num' => 'required|integer',
            'child_num' => 'required|integer',
            'baby_num' => 'required|integer',
            'source' => 'required|string',
            'needs' => 'string',
            'note' => 'string'
        ];
        $this->operator_rule = [
            '_id'=>'required|string|max:24',
            'pay_deposit' => 'boolean',
            'payment_status' => 'string',
            'deposit' => 'numeric',
            'balance' => 'numeric'
        ];
    }


    public function add(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        //預設
        $user_name = auth()->user()->contact_name;
        $user_name = auth()->user()->contact_name;


        $now_date = date('Ymd');
        $now_time = date('His');

        $validated = $validator->validated();
        //$travel_days = round((strtotime($validated['travel_end']) - strtotime($validated['travel_start']))/3600/24)+1 ;

        $validated['user_company_id'] = auth()->user()->company_id;
        $validated['own_by_id'] = auth()->user()->id;

        //找user公司名稱
        $company_data = Company::find($validated['user_company_id']);
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
            "date" => date('Y-m-d')."T".date('H:i:s'),
            "modified_by" => $user_name
        );
        array_push($validated['order_record'], $order_record_add_order_status);

        $validated['pay_deposit'] = false;
        $validated['deposit'] = 0;
        $validated['balance'] = 0;
        $validated['amount'] = 0;
        $validated['cancel_at'] = null;
        $validated['deleted_at'] = null;
        $validated['cus_group_code'] = null;
        $validated['operator_note'] = null;
        $validated['group_status'] = "未成團";
        $validated['total_people'] = $validated['adult_num'] + $validated['child_num'] + $validated['baby_num'];
        if(!array_key_exists('company',$validated)) $validated['company'] = null;



        $validated['itinerary_group_id'] = null; //團行程一開始沒有(versions)
        $validated['travel_start'] = $validated['travel_start']."T00:00:00";
        $validated['travel_end'] = $validated['travel_end']."T23:59:59";


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
            return response()->json(['error' => '沒有此id資料。'], 400);
        }

        $data_before = $data_before['document'];


        // 非旅行社及該旅行社人員不可修改訂單
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($user_company_id !== $data_before['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO381 4. 訂單狀態->參團號碼 需寫判斷
        // 寫入資料 $validated / 比較資料(在資料庫) $data_before
        if(array_key_exists('order_status',$validated) && $data_before['order_status'] !== $validated['order_status']){
            //客製化訂單狀態: 0.收到需求單 -> 1 4 / 1.已規劃行程&詢價 ->2 4 / 2.已回覆旅客 ->1 3 4 / 3.已成團 -> 4 / 4.棄單 -> X
            switch($data_before['order_status']){
                case "收到需求單":
                    //return $validated['order_status'];
                    if($validated['order_status'] !== "已規劃行程&詢價" && $validated['order_status'] !== "棄單"){
                        return response()->json(['error' => "只可改到狀態1、4"], 400);
                    }
                    break;
                case "已規劃行程&詢價":
                    if($validated['order_status'] !== "已回覆旅客" && $validated['order_status'] !== "棄單"){
                        return response()->json(['error' => "只可改到狀態2、4"], 400);
                    }
                    break;
                case "已回覆旅客":
                    if($validated['order_status'] !== "已規劃行程&詢價" && $validated['order_status'] !== "棄單" && $validated['order_status'] !== "已成團"){
                        return response()->json(['error' => "只可改到狀態1、3、4"], 400);
                    }
                    break;
                case "已成團":
                    if($validated['order_status'] !== "棄單"){
                        return response()->json(['error' => "只可改到狀態4"], 400);
                    }
                    break;
            }
            //TODO381 處理成團狀態
            if($validated['order_status'] === "已成團") $validated['group_status'] = "成團";


            //存入 order_record
            $order_record_add_order_status = array(
                "event" =>  $validated['order_status'],
                "date" => date('Y-m-d')."T".date('H:i:s'),
                "modified_by" => $user_name
            );
            $validated['order_record'] = $data_before['order_record'];
            array_push($validated['order_record'], $order_record_add_order_status);
        }

        // TODO381 5. 付款狀態 需寫判斷
        if(array_key_exists('payment_status',$validated) && $data_before['payment_status'] !== $validated['payment_status']){
            //客製化付款狀態: 0.未付款 -> 1 2 5 / 1.已付訂金 ->2 3 / 2.已付全額 -> 3 / 3.已棄單，待退款 -> 4 / 4.已棄單，已退款 -> X / 5.已棄單，免退款 -> X

            switch($data_before['payment_status']){
                case "未付款":
                    if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                        return response()->json(['error' => "只可改到狀態1、2、5"], 400);
                    }
                    break;
                case "已付訂金":
                    if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "只可改到狀態2、3"], 400);
                    }
                    break;
                case "已付全額":
                    if($validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "只可改到狀態3"], 400);
                    }
                    break;
                case "已棄單，待退款":
                    if($validated['payment_status'] !== "已棄單，已退款"){
                        return response()->json(['error' => "只可改到狀態4"], 400);
                    }
                    break;
            }
            return $validated['payment_status'];
        }

        // TODO381 6. 出團狀態 需寫判斷
        if(array_key_exists('out_status',$validated) && $data_before['out_status'] !== $validated['out_status']){
            //客製化付款狀態: 0.未出團 ->1 / 1.出團中 ->2 3 / 2.已出團，未結團 ->3 / 3.已結團 -> X
            switch($data_before['out_status']){
                case "未出團":
                    if($validated['out_status'] !== "出團中"){
                        return response()->json(['error' => "只可改到狀態1"], 400);
                    }
                    break;
                case "出團中":
                    if($validated['out_status'] !== "已出團，未結團" && $validated['out_status'] !== "已結團"){
                        return response()->json(['error' => "只可改到狀態2、3"], 400);
                    }
                    break;
                case "已出團，未結團":
                    if($validated['out_status'] !== "已結團"){
                        return response()->json(['error' => "只可改到狀態3"], 400);
                    }
                    break;
            }
        }

        $validated['last_updated_on'] = $user_name;

        // 參團編號需擋重複
        if(array_key_exists('cus_group_code', $validated)){
            $cus_orders_past = $this->requestService->find_one('cus_orders', null, 'cus_group_code', $validated['cus_group_code']);
            if($cus_orders_past !== False) return response()->json(['error' => "已存在此參團編號"], 400);
        }

        //總人數 = 各項人數相加
        $validated['total_people'] = $validated['adult_num'] + $validated['child_num'] + $validated['baby_num'];

        $cus_orders = $this->requestService->update_one('cus_orders', $validated);
        return $cus_orders;

    }


    // filter: 訂單編號, 參團編號, 旅客代表人姓名, 來源, 訂購期間(ordertime_start、ordertime_end), 行程期間, 負責人, 出團狀態, 付款狀態, 頁數

    // order_number, code, order_passenger, source, order_status, travel_start, travel_end, user_name, payment_status, out_status, page
    // code, out_status

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
        $filter['user_company_id'] = auth()->user()->company_id;


        // 找該 company 的 types
        $company_data = Company::find($company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        //訂購期間為 order_start<= created_at <=order_end
        if(array_key_exists('order_start', $filter) && array_key_exists('order_end', $filter)){
            if(strtotime($filter['order_end']) - strtotime($filter['order_start']) >= 0){
                $filter['created_at'] = array('$gte' => $filter['order_start']."T00:00:00"
                , '$lte' => $filter['order_end']."T23:59:59");
            }
            else return response()->json(['error' => '訂購結束時間不可早於訂購開始時間'], 400);
        }
        elseif(array_key_exists('order_start', $filter) && !array_key_exists('order_end', $filter)){
            return response()->json(['error' => '沒有訂購結束時間'], 400);
        }
        elseif(!array_key_exists('order_start', $filter) && array_key_exists('order_end', $filter)){
            return response()->json(['error' => '沒有訂購開始時間'], 400);
        }
        unset($filter['order_start']);
        unset($filter['order_end']);


        if(array_key_exists("travel_start", $filter) && array_key_exists('travel_end', $filter)){
            if(strtotime($filter['travel_end']) - strtotime($filter['travel_start']) > 0){
                $filter['travel_start'] = array('$gte' => $filter['travel_start']."T00:00:00"
                , '$lte' => $filter['travel_start']."T23:59:59");
                $filter['travel_end'] = array('$gte' => $filter['travel_end']."T00:00:00"
                , '$lte' => $filter['travel_end']."T23:59:59");
            }else return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'], 400);
        }

        $projection = array(
            /* "order_passenger" => 1, */

        );

        $result = $this->requestService->aggregate_search('cus_orders', $projection, $filter, $page);
        return $result;

    }

    public function get_by_id($id)
    {
        // 非旅行社及該旅行社人員不可修改訂單
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data_before = $this->requestService->find_one('cus_orders', $id, null, null);
        if($data_before===false){
            return response()->json(['error' => '沒有此id資料。'], 400);
        }

        $data_before = $data_before['document'];

        if($user_company_id !== $data_before['user_company_id']){
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
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        //將order調出
        $cus_orders_past = $this->requestService->find_one('cus_orders', null, '_id', $validated['_id']);
        if(!$cus_orders_past) return response()->json(['error' => '沒有這個id'], 400);

        $cus_orders_past = $cus_orders_past['document'];

        //判斷是否為該公司
        if($user_company_id !== $cus_orders_past['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO381 是否付訂金此欄必須為true後才可以更改
        if($validated['pay_deposit'] === false && $validated['payment_status'] === "已付訂金"){
            return response()->json(['error' => "當不須預付訂金時，付款狀態不可以為已付訂金"], 400);
        }

        // TODO381 如有要改付款狀態 必須在訂單狀態為 已成團才可以修改

        if($cus_orders_past['order_status'] === "已成團"){
            if(array_key_exists('payment_status', $validated)){
                if($cus_orders_past['payment_status'] !== $validated['payment_status']){
                    switch($cus_orders_past['payment_status']){
                        case "未付款":
                            if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                                return response()->json(['error' => "只可改到狀態1、2、5"], 400);
                            }
                            break;
                        case "已付訂金":
                            if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                                return response()->json(['error' => "只可改到狀態2、3"], 400);
                            }
                            break;
                        case "已付全額":
                            if($validated['payment_status'] !== "已棄單，待退款"){
                                return response()->json(['error' => "只可改到狀態3"], 400);
                            }
                            break;
                        case "已棄單，待退款":
                            if($validated['payment_status'] !== "已棄單，已退款"){
                                return response()->json(['error' => "只可改到狀態4"], 400);
                            }
                            break;
                    }
                }
        }else{
            return response()->json(['error' =>'沒有付款狀態欄位', 400]);
        }
        }else{
            return response()->json(['error' => '付款狀態必須是已成團，才可以更改付款狀態'], 400);
        }

        //TODO 驗算


        $cus_orders = $this->requestService->update_one('cus_orders', $validated);
        return $cus_orders;
    }

    public function passenger(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->operator_rule);
        $validated = $validator->validated();

        // 非旅行社及該旅行社人員不可修改訂單
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $data_before = $this->requestService->find_one('cus_orders', $validated['_id'], null, null);
        if($data_before===false){
            return response()->json(['error' => '沒有此id資料。'], 400);
        }

        $data_before = $data_before['document'];

        if($user_company_id !== $data_before['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        //將order調出
        $cus_orders_past = $this->requestService->find_one('cus_orders', null, '_id', $validated['_id']);
        if(!$cus_orders_past) return response()->json(['error' => '沒有這個id'], 400);

        $cus_orders_past = $cus_orders_past['document'];

        //判斷是否為該公司
        if($user_company_id !== $cus_orders_past['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        //新增旅客資訊



    }
}
