<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RequestPService;
use App\Services\ComponentLogService;

use Validator;

class ComponentCarTypeController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService, ComponentLogService $componentLogService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->componentLogService = $componentLogService;

    }

    public function list(Request $request)
    {
        Log::info('User list transportation', ['user' => auth()->user()->email, 'request' => $request->all()]);

        // Company_type: 1, Query public components belong to the company
        // Company_type: 2, Query all public components and private data belong to the company
        if (auth()->payload()->get('company_type') == 1){
            $filter = array(
                'is_display' => true,
                'owned_by' => auth()->user()->company_id
            );
            $page = 1;
            $query_private = false;
        }
        else if (auth()->payload()->get('company_type') == 2){
            $travel_agency_query = $this->travel_agency_search($request);
            $filter = $travel_agency_query['filter'];
            $page = $travel_agency_query['page'];
            $query_private = true;
        }
        else if (auth()->payload()->get('company_type') == 3) {
        }
        else{
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access transportation list', ['request' => $request->all(), 'user_id' => auth()->user()->id]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle filter content
        if(array_key_exists("base", $filter)){
            $filter['address_city'] = $filter['base'];
            unset($filter['base']);
        }


        // Handle projection content
        $projection = array(
            "_id" => 1,
            "transportation_rental_agency" => 1,
            "model" => 1,
            "passenger_seats" => 1,
            "fee" => 1,
            "imgs" => 1,
            "tel" => "\$company_tel",
            "address" => array(
                "\$concat" => array(
                    "\$address_city",
                    "\$address_town",
                    "\$address"
                )
                ),
            "private" => 1,
            "experience" => '\$car_types_private.experience',
            "created_at" => 1
        );

        $result = $this->requestService->aggregate_facet('car_types_with_company', null, $filter, $page);

        $result_data =  json_decode($result->content(), true);

        for($i = 0; $i < count($result_data['docs']); $i++){
            if(!isset($result_data['docs'][$i]['experience'])){
                $result_data['docs'][$i]['experience'] = "";
            }
        }

        return $result_data;
    }

    protected function travel_agency_search(Request $request){
        // Handle filter content
        $filter = json_decode($request->getContent(), true);
        // $filter  = $this->ensure_query_key($filter);
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
                    array('owned_by' => auth()->user()->company_id)
                    // array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
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
    // 刪除不必要的 key，避免回傳不該傳的資料
    public function ensure_query_key($filter) {
        $fields = ['transportation_rental_agency', 'model', 'passenger_seats', 'fee', 'company_tel', 'page', 'search_location', 'is_display'];
        $new_filter = array();
        foreach ($filter as $key => $value) {
            if (in_array($key, $fields)) {
                $new_filter[$key] = $value;
            }
        }
        return $new_filter;
    }
}
