<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;
use Validator;
use Carbon\Carbon;

class OrderController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestPService)
    {
        $this->middleware('auth');
        $this->requestService = $requestPService;
    }

    // 新增訂單
    public function add(Request $request)
    {
        $rule = [
            'order_passenger' => 'required|string|max:30',
            'company' => 'nullable|string|max:50',
            'email' => 'required|email',
            'phone' => 'required|string',
            'nationality' => 'required|string',
            'money' => 'required|string|max:30',
            'language' => 'required',
            'budget_min' => 'required|numeric',
            'budget_max' => 'required|numeric',
            'attendance_adults' => 'required|integer',
            'attendance_children' => 'required|integer',
            'attendance_infant' => 'required|integer',
            'source' => 'required|string',
            'needs' => 'nullable|string',
            'note' => 'nullable|string',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
        ];

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        //預設
        $user_name = auth()->user()->contact_name;
        $now_date = date('Ymd');
        $now_time = date('His');


        $validated = $validator->validated();
        $travel_days = round((strtotime($validated['travel_end']) - strtotime($validated['travel_start']))/3600/24)+1 ;

        $validated['order_state'] = "收到需求單";
        $validated['payment_state'] = "未付款";
        $validated['out_state'] = "未出團";
        $validated['order_number'] = "CUS_".$now_date."_".$now_time;
        $validated['code'] = "CUS_".$now_date."_".$travel_days."_1";
        $validated['last_updated_on'] = $user_name;
        $validated['person_in_charge'] = $user_name;
        $validated['order_record'] = array(
            "event" => "收到需求單",
            "date" => date('Y-m-d H:i:s'),
            "person_in_charge" => $user_name
        );
        $validated['version'] = array();



        $cus_order = $this->requestService->insert_one('cus_order', $validated);
        return $cus_order;

    }

}
