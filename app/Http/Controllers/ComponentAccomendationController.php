<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;

class ComponentAccomendationController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->add_rule = [
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'room' => 'nullable',
            'star' => 'nullable|string|max:2',
            'total_rooms' => 'nullable|integer',
            'category' => 'nullable|string|max:10',
            'fax' => 'nullable|string|max:10',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'map_url' => 'nullable|string',
            'imgs' => 'nullable',
            'intro_summary' => 'nullable|string|max:150',
            'refund_rule' => 'nullable|string|max:300',
            'memo' => 'nullable|string|max:4096',
            'check_in' => 'nullable|string|max:10',
            'check_out' => 'nullable|string|max:10',
            'foc' => 'nullable|string|max:200',
            'service_content' => 'nullable|string|max:200',
            'facility' => 'nullable|string|max:200',
            'is_display' => 'required|boolean',
            'is_enabled' => 'required|boolean',
            'source' => 'nullable|string|max:10',
            'bank_info' => 'nullable',
            'bank_info.bank_name' => 'nullable|string|max:20',
            'bank_info.bank_code' => 'nullable|string|max:20',
            'bank_info.account_name' => 'nullable|string|max:20',
            'bank_info.account_number' => 'nullable|string|max:20',
        ];
    }

    public function add(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->add_rule);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all(),
            ], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;
        $accomendation = $this->requestService->insert_one('accomendations', $validated);
        return $accomendation;
        
    }

    public function list(Request $request)
    {
        // Handle filter content
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

        // Handle star level
        if (array_key_exists('star', $filter)) {
            $star = $filter['star'];
            $filter['star'] = array("\$in" => $star);
        }

        // Handle room type
        if (array_key_exists('room_type', $filter)) {
            $room_type = $filter['room_type'];
            $filter['room'] = array('$elemMatch' => array('room_type' => array('$in' => $room_type)));
            unset($filter['room_type']);
        }

        if(array_key_exists('with_meals', $filter)){
            $with_meals = $filter['with_meals'];
            $filter['room'] = array('$elemMatch' => array('with_meals' => $with_meals));
            unset($filter['with_meals']);
        }

        // Handle accommendation category
        if (array_key_exists('category', $filter)) {
            $category = $filter['category'];
            $filter['category'] = array('$in' => $category);
        }


        // Handle room prices
        if (array_key_exists('fee', $filter)) {
            if ($filter['fee'] == array()) {
                unset($filter['fee']);
            }

            $price_range = array();
            if (array_key_exists('price_max', $filter['fee'])){
                $price_range['$lte'] = $filter['fee']['price_max'];
            }
            if (array_key_exists('price_min', $filter['fee'])){
                $price_range['$gte'] = $filter['fee']['price_min'];
            }
            if (!empty($price_range)){
                $filter['room'] = array('$all' => array(
                    array('$elemMatch' => array('holiday_price' => $price_range))
                ));
            }
        }
        // {'activity_items.price': {'$all':[]}}

        unset($filter['fee']);
        // Company_type: 1, Query public components belong to the company
        // Company_type: 2, Query all public components and private data belong to the company
        $company_type = auth()->payload()->get('company_type');
        $company_id = auth()->payload()->get('company_id');
        if ($company_type == 1){
            $filter['owned_by'] = auth()->user()->company_id;
            $query_private = false;
        }
        else if ($company_type == 2){
            $query_private = true;
            $filter['is_display'] = true;
        }
        else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "address_city" => 1,
            "address_town" => 1,
            "address" => 1,
            "name" => 1,
            "tel" => 1,
            "category" => 1,
            "meals" => 1,
            "imgs" => 1,
            "room" => 1,
            "star" => 1,
            "private" => 1,
            'updated_at' => 1,
            'created_at' => 1,
            "intro_summary" => 1,
            "description" => 1,
        );
        $result = $this->requestService->aggregate_facet('accomendations', $projection, $filter, $page);
        // 相容舊格式
        $current_data = $result->getData();
        foreach($current_data->docs as $doc){
            $doc->private = array('experience' => '');
        }
        $result->setData($current_data);
        return $result;
    }

}
