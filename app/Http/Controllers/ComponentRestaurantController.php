<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;

use Validator;

class ComponentRestaurantController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
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
        // Handle meal type
        if (array_key_exists('meal_type', $filter)) {
            $meal_type = $filter['meal_type'];
            $filter['meals'] = array('$elemMatch' => array('type' => $meal_type));
            unset($filter['meal_type']);
        }
        else{
            $meal_type = null;
        }

        // Handle cost_per_person range
        if (array_key_exists('cost_per_person', $filter)) {

            $price_range = array();
            if (array_key_exists('price_max', $filter['cost_per_person'])){
                $price_range['$lte'] = $filter['cost_per_person']['price_max'];
            }
            if (array_key_exists('price_min', $filter['cost_per_person'])){
                $price_range['$gte'] = $filter['cost_per_person']['price_min'];
            }
            if (!empty($price_range)){
                $filter['cost_per_person'] = $price_range;
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
            "cost_per_person" => 1,
            "meals" => 1,
            "imgs" => 1,
            "private" => 1,
            "intro_summary" => 1,
            "description" => 1,
            "created_at" => 1,
        );
        $result = $this->requestService->aggregate_facet('restaurants', $projection, $company_id, $filter, $page, $query_private);
        return $result;
    }
}
