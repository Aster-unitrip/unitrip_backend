<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Services\RequestPService;
use App\Services\ComponentLogService;


use Validator;

class ComponentActivityController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService, ComponentLogService $componentLogService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->componentLogService = $componentLogService;
        $this->add_rule = [
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
            'languages' => 'array',
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
            'website' => 'string|max:300',
            'attraction_name' => 'nullable|string|max:20',
            // 'attraction_id' => 'string',

            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'source' => 'nullable|string|max:10',
            'experience' => 'nullable|string|max:500',
            'is_display' => 'required|boolean'
        ];
        $this->edit_rule = $this->generate_edit_rule_from_add_rule($this->add_rule);

    }

    public function add(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->add_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();

        $validated['owned_by'] = $company_id;
        $validated['last_updated_on'] = auth()->user()->contact_name;
        $validated['source'] = "ta"; //??????????????????ta
        if(!array_key_exists("attraction_name", $validated)){
            $validated['attraction_name'] = null;
        }
        if(!array_key_exists("position", $validated)){
            $validated['position'] = null;
        }
        $activity = $this->requestService->insert_one('activities', $validated);
        $activity =  json_decode($activity->content(), true);

        // ?????? Log
        if($activity){
            $activity = $this->requestService->get_one('activities', $activity['inserted_id']);
            $activity =  json_decode($activity->content(), true);
            $filter = $this->componentLogService->recordCreate('activities', $activity);
            $create_components_log = $this->requestService->insert_one("components_log", $filter);
            Log::info('User add activity', ['user' => auth()->user()->email, 'request' => $request->all()]);
            return $activity;
        }else{
            Log::info('User add activity failed', ['user' => auth()->user()->email, 'request' => $request->all()]);
            return response()->json(['error' => 'add activity failed'], 400);
        }

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
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $company_id = auth()->user()->company_id;
        $validated['owned_by'] = $company_id;
        $validated['last_updated_on'] = auth()->user()->contact_name;
        if(!array_key_exists("attraction_name", $validated)){
            $validated['attraction_name'] = null;
        }

        // $validated['attraction_id'] = array(
        //     "_id" => array("\$oid" => $validated['attraction_id']['_id'])
        // );

        $record = $this->requestService->get_one('activities', $validated['_id']);
        $content =  json_decode($record->content(), true);

        if(auth()->payload()->get('company_type') == 1){
            if($content['is_display'] == true && $content['owned_by'] == $company_id){
                $activity = $this->requestService->update_one('activities', $validated);
            }
            else{
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        }
        else if(auth()->payload()->get('company_type') == 2){
            if($content['is_display'] == false && $content['owned_by'] == $company_id){
                $activity = $this->requestService->update_one('activities', $validated);
            }
            else if($content['is_display'] == true && $content['owned_by'] == $company_id){
                $activity = $this->requestService->update_one('activities', $validated);
            }
            else{
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        }
        else if(auth()->payload()->get('company_type') == 3){
            $activity = $this->requestService->update_one('activities', $validated);
        }
        else{
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        return $activity;
    }

    // ????????????????????????is_display == true & is_enabled == true
    // ????????????????????????is_display == false & owned_by == ???????????? id & is_enabled == true
    // ?????????????????????????????????is_display == true or (owned_by == ???????????? id & is_enabled == true)
    // ??????????????????????????????????????????
    public function list(Request $request)
    {
        if (auth()->payload()->get('company_type') == 1) {
            $filter = array(
                'is_display' => true,
                'owned_by' => auth()->user()->company_id
            );
            $page = 1;
        }
        else if(auth()->payload()->get('company_type') == 2){
            $travel_agency_query = $this->travel_agency_search($request);
            $filter = $travel_agency_query['filter'];
            $page = $travel_agency_query['page'];
        }
        else if(auth()->payload()->get('company_type') == 3){
        }
        else{
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access attractions list. Wrong identity.', ['request' => $request->all(), 'user' => auth()->user()->email]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "address_city" => 1,
            "address" => 1,
            "address_town" => 1,
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
            "created_at" => 1,
            "is_display" => 1,
            "activity_company_name" => 1,
            "position"=> 1,
        );
        // ??????????????????????????????????????????
        if(array_key_exists('activity_company_name', $filter)){
            $filter['activity_company_name'] = array('$regex' => trim($filter['activity_company_name']));
        }
        if(array_key_exists('name', $filter)){
            // $filter['name'] = array('$regex' => $filter['name'], '$options' => 'i');
            $filter['name'] = array('$regex' => trim($filter['name']));

        }
        $result = $this->requestService->aggregate_facet('activities', $projection, $filter, $page);

        // ???????????????
        $current_data = $result->getData();
        foreach($current_data->docs as $doc){
            $doc->private = array('experience' => '');
        }
        $result->setData($current_data);

        return $result;
    }

    private function travel_agency_search(Request $request) {
        // Handle filter content
        $filter = json_decode($request->getContent(), true);
        $filter  = $this->ensure_query_key($filter);
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
            if (array_key_exists('price_max', $filter['fee'])) {
                $price_range['$lte'] = $filter['fee']['price_max'];
            }
            if (array_key_exists('price_min', $filter['fee'])) {
                $price_range['$gte'] = $filter['fee']['price_min'];
            }
            if (!empty($price_range)) {
                // ?????????????????????????????????????????????
                // $filter['activity_items'] = array('$all' => array(
                //     array('$elemMatch' => array('price' => $price_range))
                // ));
                $filter['activity_items'] = array('$elemMatch' => array('price' => $price_range));
            }
        }

        unset($filter['fee']);

        if (array_key_exists('stay_time', $filter)) {
            $filter['stay_time'] = array('$lte' => $filter['stay_time']);
        }

        if(array_key_exists('search_location', $filter)){
            if($filter['search_location'] == 'public'){
                $filter['is_display'] = true;
            }
            else if($filter['search_location'] == 'private'){
                $filter['is_display'] = false;
                $filter['owned_by'] = auth()->user()->company_id;
            }
            else if($filter['search_location'] == 'all'){
                $filter['$or'] = array(
                    array('is_display' => true),
                    array('owned_by' => auth()->user()->company_id)
                );
            }
            else if($filter['search_location'] == 'enabled'){
                $filter['$or'] = array(
                    array('is_display' => true),
                    array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
                );
            }
            else{
                return response()->json(['error' => 'search_location must be public, private or all'], 400);
            }
        }
        else if(!array_key_exists('search_location', $filter)){
            $filter['$or'] = array(
                array('is_display' => true),
                array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
            );
        }
        unset($filter['search_location']);

        return array('page'=>$page, 'filter'=>$filter);
    }

    // ?????????????????? key?????????????????????????????????
    public function ensure_query_key($filter) {
        $fields = ['address_city', 'address_town', 'name', 'categories', 'page', 'search_location', 'fee', 'stay_time', 'activity_company_name'];
        $new_filter = array();
        foreach ($filter as $key => $value) {
            if (in_array($key, $fields)) {
                $new_filter[$key] = $value;
            }
        }
        return $new_filter;
    }

    protected function generate_edit_rule_from_add_rule($rule)
    {
        $add_rule = [
            "_id" => 'required|string'
        ];
        $rule += $add_rule;
        // unset($rule['is_display']);
        return $rule;
    }

}
