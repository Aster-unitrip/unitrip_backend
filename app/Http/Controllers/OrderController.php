<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;

use Validator;




class OrderController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestPService)
    {
        $this->middleware('auth');
        $this->requestService = $requestPService;

        // 前端條件
        $this->rule = [
            'order_passenger' => 'required|string|max:30',
            'company' => 'nullable|string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'currency' => 'required|string|max:30',
            'languages' => 'required',
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
            'cus_group_code' => 'required|string',
            'order_status' => 'string',
/*             'order_passenger' => 'required|string|max:30',
            'company' => 'nullable|string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'currency' => 'required|string|max:30',
            'languages' => 'required',
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
            'payment_status' => 'required|string',
            'out_status' => 'required|string', */

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

        $now_date = date('Ymd');
        $now_time = date('His');

        $validated = $validator->validated();
        //$travel_days = round((strtotime($validated['travel_end']) - strtotime($validated['travel_start']))/3600/24)+1 ;

        $validated['user_company_id'] = auth()->user()->company_id;
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

        $validated['deposit_status'] = "未付款";
        $validated['deposit'] = 0;
        $validated['balance_status'] = "未付款";
        $validated['balance'] = 0;
        $validated['amount'] = 0;
        $validated['cancel_at'] = null;
        $validated['deleted_at'] = null;
        $validated['cus_group_code'] = null;
        $validated['versions'] = array();
        $validated['travel_start'] = $validated['travel_start']."T00:00:00";
        $validated['travel_end'] = $validated['travel_end']."T23:59:59";


        $cus_orders = $this->requestService->insert_one('cus_orders', $validated);
        return $cus_orders;

    }

    public function edit(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $user_name = auth()->user()->contact_name;
        $validated = $validator->validated();
        $validated['last_updated_on'] = $user_name;

        // 參團編號需擋重複
        $cus_orders_past = $this->requestService->find_one('cus_orders', null, 'cus_group_code', $validated['cus_group_code']);
        if($cus_orders_past !== False) return response()->json(['error' => "已存在此參團編號"], 400);

        // TODO: 當 order_status 更換必須 push 到 order_record
        $order_status_past = $this->requestService->get_one('cus_orders', $validated['_id']);
        $content =  json_decode($order_status_past->content(), true);
        if($content['order_status'] !== $validated['order_status']){

            $order_record_add_order_status = array(
                "event" =>  $validated['order_status'],
                "date" => date('Y-m-d')."T".date('H:i:s'),
                "modified_by" => $user_name
            );
            $validated['order_record'] = $content['order_record'] + $order_record_add_order_status;

            /* $validated['order_record'] =  array_push($content['order_record'], $order_record_add_order_status);*/
            return $order_record_add_order_status.$validated['order_record'];
        }


        return $validated['order_record'];

        /* $cus_orders = $this->requestService->update('cus_orders', $validated); */

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
        $company_type = auth()->payload()->get('company_type');
        $filter['user_company_id'] = auth()->user()->company_id;
        if ($company_type !== 1){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        //訂購期間為 order_time_start<= created_at <=order_time_end
        if(array_key_exists('order_time_start', $filter) && array_key_exists('order_time_end', $filter)){
            if(strtotime($filter['order_time_end']) - strtotime($filter['order_time_start']) > 0){
                $filter['created_at'] = array('$gte' => $filter['order_time_start']."T00:00:00"
                , '$lte' => $filter['order_time_end']."T23:59:59");
            }
            else return response()->json(['error' => '訂購結束時間不可早於訂購開始時間'], 400);
        }
        elseif(array_key_exists('order_time_start', $filter) && !array_key_exists('order_time_end', $filter)){
            return response()->json(['error' => '沒有訂購結束時間'], 400);
        }
        elseif(!array_key_exists('order_time_start', $filter) && array_key_exists('order_time_end', $filter)){
            return response()->json(['error' => '沒有訂購開始時間'], 400);
        }
        unset($filter['order_time_start']);
        unset($filter['order_time_end']);


        //return $filter;


        $projection = array(
            /* "order_passenger" => 1, */

        );

        $result = $this->requestService->aggregate_search('cus_orders', $projection, $filter, $page);
        return $result;

    }

}
