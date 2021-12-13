<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestService;

use Validator;

class ActivityController extends Controller
{
    private $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
    }

    public function add(Request $request)
    {
        $rule = [
            'attraction_name' => 'string|max:20',
            'attraction_id' => 'string',
            'name' => 'required|max:30',
            'tel' => 'required|max:15',
            'fax' => 'max:15',
            'categories' => 'required',
            'language' => 'required',
            'gather_at' => 'required',
            'dismiss_at' => 'required',
            'activity_location' => 'string|max:300',
            'imgs' => 'required',
            'intro_summary' => 'string|max:150',
            'description' => 'string|max:300',
            'activity_items' => 'required',
            'price_include' => 'required',
            'price_exclude' => 'required',
            'attention' => 'required',
            'detail_before_buy' => 'string|max:300',
            'additional_fee' => 'string|max:300',
            'refund' => 'string|max:300',
            'note' => 'string|max:300',
            'is_display' => 'required|boolean'
        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $activity = $this->requestService->insert_one('activities', $validated);
        return $activity;
 
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
        // Handle ticket prices
        if (array_key_exists('fee', $filter)) {
            
            $price_range = array();
            if (array_key_exists('price_max', $filter['fee'])){
                $price_range['$lte'] = $filter['fee']['price_max'];
            }
            if (array_key_exists('price_min', $filter['fee'])){
                $price_range['$gte'] = $filter['fee']['price_min'];
            }
            if (empty($price_range)){
                $filter['activity_items.price'] = array('$all' => array($price_range));
            }
        }
        // {'activity_items.price': {'$all':[]}}

        unset($filter['fee']);

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "address_city" => 1,
            "address_town" => 1,
            "address" => 1,
            "name" => 1,
            "attraction_name" => 1,
            "tel" => 1,
            "activity_items" => 1,
            "max_pax_size" => 1,
            "stay_time" => 1,
            "imgs" => 1,
            "experience" => 1,
        );
        $result = $this->requestService->aggregate_filter('activities', $projection, $filter, $page);
        return $result;
    }

    public function get_by_id($id)
    {
        $result = $this->requestService->get_one('activities', $id);
        return $result;
    }

    public function edit(Request $request)
    {
        $rule = [
            '_id' => 'required|string|max:24',
            'attraction_name' => 'string|max:20',
            'attraction_id' => 'array',
            'name' => 'required|max:30',
            'tel' => 'required|max:15',
            'fax' => 'max:15',
            'categories' => 'required',
            'language' => 'required',
            'gather_at' => 'required',
            'dismiss_at' => 'required',
            'activity_location' => 'string|max:300',
            'imgs' => 'required',
            'intro_summary' => 'string|max:150',
            'description' => 'string|max:300',
            'activity_items' => 'required',
            'price_include' => 'required',
            'price_exclude' => 'required',
            'attention' => 'required',
            'detail_before_buy' => 'string|max:300',
            'additional_fee' => 'string|max:300',
            'refund' => 'string|max:300',
            'note' => 'string|max:300',
            'is_display' => 'required|boolean',
            'created_at' => 'required|string'
        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $validated['attraction_id'] = array(
            "_id" => array("\$oid" => $validated['attraction_id']['_id'])
        );
        $activity = $this->requestService->update('activities', $validated);
        return $activity;

    }
}