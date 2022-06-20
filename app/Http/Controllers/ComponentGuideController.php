<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;
use Illuminate\Support\Facades\Log;
use App\Services\ComponentLogService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ComponentGuideController extends Controller
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

        // Handle filter content
        $filter = json_decode($request->getContent(), true);

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
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access guide list', ['request' => $request->all(), 'user_id' => auth()->user()->id]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle "can_drive" field
        if (array_key_exists('can_drive', $filter)) {
            $pos = array("1", 1, "true", "True", true);
            $neg = array("0", 0, "false", "False", false);
            if (in_array($filter['can_drive'], $pos)){
                $filter['can_drive'] = true;
            }
            else if (in_array($filter['can_drive'], $neg)){
                $filter['can_drive'] = false;
            }
        }

        // Handle "language" field
        if (array_key_exists('languages', $filter)){
            $languages = $filter['languages'];
            $filter['languages'] = array('$in' => $languages);
        }
        // 導遊名稱模糊搜尋
        if(array_key_exists('name', $filter)){
            // $filter['name'] = array('$regex' => $filter['name'], '$options' => 'i');
            $filter['name'] = array('$regex' => $filter['name']);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "gender" => 1,
            "name" => 1,
            "categories" => 1,
            "languages" => 1,
            "imgs" => 1,
            "fee" => 1,
            "cell_phone" => 1,
            "private" => 1,
            "created_at" => 1,
            "is_display" =>1
        );

        $result = $this->requestService->aggregate_facet('guides', $projection, $filter, $page);
        // 相容舊格式
        $current_data = $result->getData();
        foreach($current_data->docs as $doc){
            $doc->private = array('experience' => '');
        }
        $result->setData($current_data);
        return $result;
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
        $fields = ['name', 'gender', 'categories', 'languages', 'company_tel', 'can_drive', 'page', 'search_location', 'is_display'];
        $new_filter = array();
        foreach ($filter as $key => $value) {
            if (in_array($key, $fields)) {
                $new_filter[$key] = $value;
            }
        }
        return $new_filter;
    }

}
