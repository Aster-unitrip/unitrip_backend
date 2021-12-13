<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestService;


class RestaurantController extends Controller
{
    private $requestService;

    public function __construct(RequestService $requestService)
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
        $result = $this->requestService->aggregate_filter('restaurants', $projection, $filter, $page);
        return $result;
    }
}