<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttractionService;
use App\Services\RequestPService;
use App\Services\ComponentLogService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

use Validator;

class ComponentAttractionController extends Controller
{
    private $attractionService;
    private $requestService;

    public function __construct(AttractionService $attractionService, RequestPService $requestPService, ComponentLogService $componentLogService)
    {
        $this->middleware('auth');
        $this->attractionService = $attractionService;
        $this->requestService = $requestPService;
        $this->componentLogService = $componentLogService;
        $this->add_rule = [
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'fax' => 'nullable|string|max:12',
            'historic_level' => 'nullable|string|max:6',
            'org_name' => 'string|max:20',
            'categories' => 'array',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'business_time' => 'nullable',
            'stay_time' => 'nullable|integer',
            'intro_summary' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:300',
            'ticket' => 'nullable',
            'memo' => 'nullable|string|max:4096',
            'parking' => 'nullable|string|max:500',
            'attention' => 'nullable|string|max:500',
            'experience' => 'nullable|string|max:500',
            'is_display' => 'required|boolean',
            'is_enabled' => 'required|boolean',
            'imgs' => 'nullable',
            'source' => ['required', Rule::in(['unitrip', 'supplier', 'gov', 'kkday', 'ota', 'ta'])],
            'bank_info' => 'array',
            'bank_info.sort' => 'nullable|string|max:20',
            'bank_info.bank_name' => 'nullable|string|max:20',
            'bank_info.bank_code' => 'nullable|string|max:20',
            'bank_info.account_name' => 'nullable|string|max:20',
            'bank_info.account_number' => 'nullable|string|max:20',
        ];
        $this->edit_rule = $this->generate_edit_rule_from_add_rule($this->add_rule);

    }

    // ???????????????????????????????????????????????????
    // ?????????????????????????????????????????????????????????????????????????????????????????????(is_enabled)
    // ????????????????????????????????????????????????????????????(is_display)
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
        if(!array_key_exists("position", $validated)){
            $validated['position'] = null;
        }
        $attraction = $this->requestService->insert_one('attractions', $validated);
        $attraction =  json_decode($attraction->content(), true);

        // ?????? Log
        if($attraction){
            $attraction = $this->requestService->get_one('attractions', $attraction['inserted_id']);
            $attraction =  json_decode($attraction->content(), true);
            $filter = $this->componentLogService->recordCreate('attractions', $attraction);
            $create_components_log = $this->requestService->insert_one("components_log", $filter);
            Log::info('User add attraction', ['user' => auth()->user()->email, 'request' => $request->all()]);
            return $attraction;
        }else{
            Log::info('User add attraction failed', ['user' => auth()->user()->email, 'request' => $request->all()]);
            return response()->json(['error' => 'add attraction failed'], 400);
        }


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
        } else if (auth()->payload()->get('company_type') == 2) {
            $travel_agency_query = $this->travel_agency_search($request);
            $filter = $travel_agency_query['filter'];
            $page = $travel_agency_query['page'];
        } else if (auth()->payload()->get('company_type') == 3) {

        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access attractions list. Wrong identity.', ['request' => $request->all(), 'user' => auth()->user()->email]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle projection content
        $projection = array(
                "_id" => 1,
                "address_city" => 1,
                "address_town" => 1,
                "address" => 1,
                "categories" => 1,
                "name" => 1,
                "categories" => 1,
                "tel" => 1,
                "ticket" => 1,
                "imgs" => 1,
                "private" => 1,
                "intro_summary" => 1,
                "description" => 1,
                "experience" => 1,
                "position"=> 1,
                "is_display" => 1,
                "source" => 1,
                "updated_at" => 1,
                "created_at" => 1
            );
        // ????????????????????????
        if(array_key_exists('name', $filter)){
            // $filter['name'] = array('$regex' => $filter['name'], '$options' => 'i');
            $filter['name'] = array('$regex' => trim($filter['name']));
        }

        $result = $this->requestService->aggregate_facet('attractions', $projection, $filter, $page);
        // ???????????????
        $current_data = $result->getData();
        foreach($current_data->docs as $doc){
            $doc->private = array('experience' => '');
        }
        $result->setData($current_data);

        return $result;
    }

    // ?????????(type: 1)????????????????????????
    // ?????????(type: 2)????????????????????????????????????????????????
    // ?????????(type: 3)????????????????????????
    public function get_by_id($id)
    {
        $company_id = auth()->user()->company_id;
        $result = $this->requestService->get_one('attractions', $id);
        $content =  json_decode($result->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == false) {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] != $company_id) {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {

        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access attractions list. Wrong identity.', ['user' => auth()->user()->email]);
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

    // ?????????(type: 1)???????????????????????????
    // ???????????????????????????????????????????????????
    // ??????????????????????????????????????????
    // ?????????????????????????????????
    // ?????????????????????????????????????????????????????? is_display
    // ????????????????????????????????? move_from_private_to_public ??? move_from_public_to_private
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
        $record = $this->requestService->get_one('attractions', $validated['_id']);
        $content =  json_decode($record->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == true && $content['owned_by'] == $company_id) {
                $attraction = $this->requestService->update_one('attractions', $validated);
            } else {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] == $company_id) {
                $attraction = $this->requestService->update_one('attractions', $validated);
            } else if ($content['is_display'] == true && $content['owned_by'] == $company_id) {
                $attraction = $this->requestService->update_one('attractions', $validated);
            } else {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {
            $attraction = $this->requestService->update_one('attractions', $validated);
        } else {
            return response()->json(['error' => 'Wrong identity.'], 400);
        }
        // $attraction = $this->requestService->update_one('attractions', $validated);
        return $attraction;

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
            if ($filter['fee']['free'] == true){
                $filter['ticket.free'] = true;
            }else if ($filter['fee']['free'] == false){
                $filter['ticket.free'] = false;
                $price_range = array();
                if (array_key_exists('price_max', $filter['fee'])){
                    $price_range['$lte'] = $filter['fee']['price_max'];
                }
                if (array_key_exists('price_min', $filter['fee'])){
                    $price_range['$gte'] = $filter['fee']['price_min'];
                }
                if (count($price_range) > 0){
                    $filter['ticket.full_ticket.price'] = $price_range;
                }
            }
        }
        unset($filter['fee']);

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
        $fields = ['address_city', 'address_town', 'name', 'categories', 'page', 'search_location', 'fee'];
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
