<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;

use Validator;

class ComponentCarTypeController extends Controller
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
        if(array_key_exists("base", $filter)){
            $filter['address_city'] = $filter['base'];
            unset($filter['base']);
        }

        // unset($filter['fee']);
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
        }
        else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        $unwind[0] = array(
            "path" => '$car_types',
        );

        // lookup database
        $lookup = array(
            "from" => 'car_types',
            "localField" => 'car_types',
            "foreignField" => '_id',
            "as" => 'car_types_data'
        );

        $unwind[1] = array(
            "path" => '$car_types_data',
            "preserveNullAndEmptyArrays" => true,
        );

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "name" => 1,
            "tel" => 1,
            "transportation_rental_agency" => "\$car_types_data.transportation_rental_agency",
            "model" => "\$car_types_data.model",
            "passenger_seats" => "\$car_types_data.passenger_seats",
            "fee" => "\$car_types_data.fee",
            "imgs" => "\$car_types_data.imgs",
            "address_city" => 1,
            "address" => 1
        );
        $result = $this->requestService->aggregate_search_car_table('car_rental_company', $projection, $filter, $lookup, $unwind, $page=0);
        return $result;
    }

/*     public function list(Request $request)
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
        if(array_key_exists("base", $filter)){
            $filter['address_city'] = $filter['base'];
            unset($filter['base']);
        }

        // unset($filter['fee']);
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
            //$filter['is_display'] = true;
        }
        else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
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
        );
        $result = $this->requestService->aggregate_facet('car_types_with_company', $projection, $company_id, $filter, $page, $query_private);
        return $result;
    } */
}
