<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Services\RequestPService;

use Validator;

class ComponentActivityController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
    }

    public function add(Request $request)
    {
        $rule = [
            'activity_company_name' => 'required|string|max:30',
            'name' => 'required|string|max:30',
            'tel' => 'required|string|max:20',
            'fax' => 'nullable|string|max:15',
            'email' => 'nullable|string|max:30',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'gather_at' => 'required',
            'dismiss_at' => 'required',
            'activity_location' => 'string|max:300',
            'language' => 'array',
            'categories' => 'array',
            'pax_size_threshold' => 'nullable|integer',
            'stay_time' => 'nullable|numeric',
            'imgs' => 'nullable',
            'intro_summary' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
            'activity_items' => 'nullable',
            'activity_items.sort' => 'nullable|integer|max:5',
            'activity_items.name' => 'nullable|string|max:30',
            'activity_items.price' => 'nullable|integer|min:0',
            'activity_items.memo' => 'nullable|string|max:50',
            'price_include' => 'required',
            'price_exclude' => 'required',
            'attention' => 'nullable',
            'detail_before_buy' => 'string|max:300',
            'additional_fee' => 'string|max:300',
            'refund' => 'string|max:300',
            'note' => 'string|max:300',
            'bank_info' => 'array',
            'bank_info.sort' => 'nullable|integer|max:20',
            'bank_info.bank_name' => 'nullable|string|max:20',
            'bank_info.bank_code' => 'nullable|string|max:20',
            'bank_info.account_name' => 'nullable|string|max:20',
            'bank_info.account_number' => 'nullable|string|max:20',
            'is_enabled' => 'required|boolean',
            'attraction_name' => 'string|max:20',
            'attraction_id' => 'string',
            // 不確定是否需加
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'source' => 'nullable|string|max:10',
            'experience' => 'nullable|string|max:500',
            'is_display' => 'required|boolean'
        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();

        $validated['owned_by'] = $company_id;
        $validated['source'] = "ta"; //旅行社預設為ta

        $activity = $this->requestService->insert_one('activities', $validated);
        return $activity;
    }

    public function get_by_id($id)
    {
        $company_id = auth()->user()->company_id;
        $result = $this->requestService->get_one('activities', $id);
        $content =  json_decode($result->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == false) {
                return response()->json(['error' => 'You can not access this activity'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] != $company_id) {
                return response()->json(['error' => 'You can not access this activity'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {

        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access activities list. Wrong identity.', ['user' => auth()->user()->email]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        if (array_key_exists('imgs', $content)){
            foreach ($content['imgs'] as $value){
                $n = 0;
                $split_url = explode('/', $value['url']);
                $content['imgs'][$n]['filename'] = end($split_url);
            }
        }

        return $content;

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
            'attention' => 'nullable',
            'detail_before_buy' => 'string|max:300',
            'additional_fee' => 'string|max:300',
            'refund' => 'string|max:300',
            'note' => 'string|max:300',
            'is_display' => 'required|boolean',
            'created_at' => 'required|string',
            "intro_summary" => 1,
            "description" => 1,
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

    public function list(Request $request)
    {
        // Handle filter content
        $filter = json_decode($request->getContent(), true);
        if (array_key_exists('page', $filter)) {
            $page = $filter['page'];
            unset($filter['page']);
            if ($page <= 0) {
                return response()->json(['error' => 'page must be greater than 0'], 400);
            } else {
                $page = $page - 1;
            }
        } else {
            $page = 0;
        }
        // Handle ticket prices
        if (array_key_exists('fee', $filter)) {

            $price_range = array();
            if (array_key_exists('price_max', $filter['fee'])) {
                $price_range['$lte'] = $filter['fee']['price_max'];
            }
            if (array_key_exists('price_min', $filter['fee'])) {
                $price_range['$gte'] = $filter['fee']['price_min'];
            }
            if (!empty($price_range)) {
                $filter['activity_items'] = array('$all' => array(
                    array('$elemMatch' => array('price' => $price_range))
                ));
            }
        }
        // {'activity_items.price': {'$all':[]}}

        unset($filter['fee']);
        // Company_type: 1, Query public components belong to the company
        // Company_type: 2, Query all public components and private data belong to the company
        $company_type = auth()->payload()->get('company_type');
        $company_id = auth()->payload()->get('company_id');
        if ($company_type == 1) {
            $filter['owned_by'] = auth()->user()->company_id;
            $query_private = false;
        } else if ($company_type == 2) {
            $query_private = true;
            $filter['is_display'] = true;
        } else {
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "gather_at" => 1,
            "name" => 1,
            "attraction_name" => 1,
            "tel" => 1,
            "categories" => 1,
            "activity_items" => 1,
            "pax_size_threshold" => 1,
            "max_pax_size" => 1,
            "stay_time" => 1,
            "imgs" => 1,
            "private" => 1,
            "description" => 1,
            "intro_summary" => 1,
            "updated_at" => 1,
            "created_at" => 1

        );
        $result = $this->requestService->aggregate_facet('activities', $projection, $filter, $page);
        // 相容舊格式
        $current_data = $result->getData();
        foreach($current_data->docs as $doc){
            $doc->private = array('experience' => '');
        }
        $result->setData($current_data);
        return $result;
    }




}
