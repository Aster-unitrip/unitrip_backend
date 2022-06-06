<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\ComponentCategoryService;
use App\Services\ComponentLogService;
use Illuminate\Support\Facades\Log;
class ComponentCategoryController extends Controller
{
    private $componentCategoryService;

    public function __construct(ComponentCategoryService $componentCategoryService, ComponentLogService $componentLogService, RequestPService $requestPService)
    {
        $this->middleware('auth');
        $this->componentCategoryService = $componentCategoryService;
        $this->requestService = $requestPService;
        $this->componentLogService = $componentLogService;
    }

    public function parentCategories()
    {
        return $this->componentCategoryService->getParentCategories();
    }

    public function childCategories(Request $request)
    {
        if ($request->has('parent_category')) {
            return $this->componentCategoryService->getChildCategories($request->input('parent_category'));
        }
    }

    // 先確認此元件屬不屬於他自己，而且必須是子槽資料
    public function copy_from_private_to_public(Request $request) {
        $query = json_decode($request->getContent(), true);
        // 查詢元件 處理沒找到元件的使用情境
        if(array_key_exists('type', $query) && array_key_exists('_id',$query)){
            $component = $this->requestService->get_one($query['type'], $query['_id']);
            $component = json_decode($component->content(), true);
            // 判斷是否有該元件
            if(array_key_exists('count', $component) && $component['count'] === 0){
                return response()->json(['error' => 'You must input correct _id or this _id is not exist.'], 400);
            }
            // 元件分享只可以為子到母
            if($component['is_display'] === true){
                return response()->json(['error' => 'This component is public, you can not copy to public again.'], 400);
            }
        }else{
            return response()->json(['error' => 'You must input type and _id'], 400);
        }

        // 查詢
        // 母到(子到母)
        $filter = $this->componentLogService->checkPrivateToPublic($component);
        $searchResultPrivateToPublic = $this->requestService->aggregate_search("components_log", null, $filter, $page=0);
        $searchResultPrivateToPublic = json_decode($searchResultPrivateToPublic->content(), true);
        // (母到子)到母
        $filter = $this->componentLogService->checkPublicToPrivate($component);
        $searchResultPublicToPrivate = $this->requestService->aggregate_search("components_log", null, $filter, $page=0);
        $searchResultPublicToPrivate = json_decode($searchResultPublicToPrivate->content(), true);

        $resultSearchLog = $this->componentLogService->checkLogFilter($searchResultPrivateToPublic, $searchResultPublicToPrivate);

        if($resultSearchLog === true){// 確認該元件是否屬於該公司
            $component['is_display'] = true;
            $component['is_enabled'] = true;
            $component = $this -> ensure_private2public_key($query['type'], $component);

            $private2public = $this->requestService->insert_one($query['type'], $component);
            $private2public = json_decode($private2public->content(), true);
            Log::info("Copied component to public", ['id' => $private2public['inserted_id'], 'user' => auth()->user()->email]);

            // 紀錄該元件是否已複製至母槽過
            $add_flied = $this->componentLogService->recordPrivateToPublic($query, $private2public['inserted_id'], $component);
            $add_flied = $this->requestService->insert_one('components_log', $add_flied);

            return response()->json([
                'message' => 'Successfully copied component to public',
                'id' => $private2public['inserted_id']
            ]);
        }
        else{
            return $resultSearchLog;
        }
    }

    // 把元件從母槽複製子槽
    // 複製進子槽不必考慮權限
    public function copy_from_public_to_private(Request $request) {
        $query = json_decode($request->getContent(), true);
        // 查詢元件 處理沒找到元件的使用情境
        if(array_key_exists('type', $query) && array_key_exists('_id',$query)){
            $component = $this->requestService->get_one($query['type'], $query['_id']);
            $component = json_decode($component->content(), true);
            // 判斷是否有該元件
            if(array_key_exists('count', $component) && $component['count'] === 0){
                return response()->json(['error' => 'You must input correct _id or this _id is not exist.'], 400);
            }
            // 元件分享只可以為母到子
            if($component['is_display'] === false){
                return response()->json(['error' => 'This component is private, you can not copy to private again.'], 400);
            }
        }else{
            return response()->json(['error' => 'You must input type and _id'], 400);
        }

        // 查詢

        // 子到(母到子)
        $filter = $this->componentLogService->checkPublicToPrivate($component);
        $searchResultPublicToPrivate = $this->requestService->aggregate_search("components_log", null, $filter, $page=0);
        $searchResultPublicToPrivate = json_decode($searchResultPublicToPrivate->content(), true);

        // (子到母)到母
        $filter = $this->componentLogService->checkPrivateToPublic($component);
        $searchResultPrivateToPublic = $this->requestService->aggregate_search("components_log", null, $filter, $page=0);
        $searchResultPrivateToPublic = json_decode($searchResultPrivateToPublic->content(), true);


        $resultSearchLog = $this->componentLogService->checkLogFilter($searchResultPrivateToPublic, $searchResultPublicToPrivate);

        if($resultSearchLog === true){// 確認該元件是否屬於該公司
        // 修改擁有公司
        $component['owned_by'] = auth()->user()->company_id;
        $component['is_display'] = false;
        $public2private = $this->requestService->insert_one($query['type'], $component);
        $public2private = json_decode($public2private->content(), true);
        Log::info("Copied component to private", ['id' => $public2private['inserted_id'], 'user' => auth()->user()->email]);
        // 紀錄該元件是否已複製至母槽過
        $add_flied = $this->componentLogService->recordPublicToPrivate($query, $public2private['inserted_id'], $component);
        $add_flied = $this->requestService->insert_one('components_log', $add_flied);

        return response()->json([
            'message' => 'Successfully copied component to private',
            'id' => $public2private['inserted_id']
        ]);
        }
        else{
            return $resultSearchLog;
        }





    }

    // 刪除不必要的 key，避免回傳不該傳的資料
    public function ensure_private2public_key($type, $component) {

        // 區分類型
        // 景點 : ticket，母槽元件 ticket 只顯示票種不要票價
        if($type === 'attractions'){
            foreach($component['ticket'] as $key => $value){
                if ($key != 'free' & array_key_exists('price', $component['ticket'])) {
                    $key['price'] = 0;
                }
            }
        }
        // 餐廳 : cost_per_person, meals, driver_tour_memo
        if($type === 'restaurants'){
            $component['cost_per_person'] = array();
            $component['meals'] = array();
            $component['driver_tour_memo'] = "";
        }
        // 住宿 : foc,  room
        if($type === 'accomendations'){
            $component['foc'] = null;
            $component['room'] = array();
        }
        // 體驗 : activity_items
        if($type === 'activities'){
            $component['activity_items'] = array();
        }

        // $component['experience'] = '';
        $component['source'] = 'ta';
        $component['updated_at'] = date('Y-m-d H:i:s');
        $component['created_at'] = date('Y-m-d H:i:s');
        unset($component['_id']);

        return $component;
    }
}
