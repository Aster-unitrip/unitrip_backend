<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\MiscService;
use App\Services\RequestPService;

use App\Models\User;
use App\Models\Company;


class MiscController extends Controller
{
    private $miscService;
    private $requestService;


    public function __construct(MiscService $miscService, RequestPService $requestPService)
    {
        // $this->middleware('auth');
        $this->miscService = $miscService;
        $this->requestService = $requestPService;

    }


    public function cityTown()
    {
        return $this->miscService->getCityTown();
    }


    public function bankCode()
    {
        return $this->miscService->getBankCode();
    }

    public function historicLevel()
    {
        return $this->miscService->getHistoricLevel();
    }

    public function travelAgencyType()
    {
        return $this->miscService->getTravelAgencyType();
    }

    public function organization()
    {
        return $this->miscService->getOrganization();
    }

    public function nationality()
    {
        return $this->miscService->getNationality();
    }


    public function order_source()
    {
        return $this->miscService->getOrderSource();
    }

    public function company_employee()
    {
        // 所有資料傳回(未過濾)
        $owned_by = auth()->user()->company_id;
        $same_company_users_data = User::where('company_id', $owned_by)->get();
        return $same_company_users_data;
    }

    public function check_duplicate(Request $request)
    {
        //傳入要查詢的欄位、數值、id
        $filter = json_decode($request->getContent(), true);
        $filter['owned_by'] = auth()->user()->company_id;

        if($filter['owned_by'] === null){
            return response()->json(['error' => "請確實登入系統(找不到使用者公司id)"]);
        }

        $fieldId = null;
        if(array_key_exists('fieldName', $filter) && array_key_exists('value', $filter)){
            $array_field = array(0 => 'cus_group_code', 1 => 'code', 2 => 'name');
            $key = array_search($filter['fieldName'], $array_field);
            if($key === 0){
                if($filter['value'] === ""){
                    return false; //不可以空
                }
                $fieldDB = "cus_orders";
                $filter['cus_group_code'] = $filter['value'];
            }
            else if($key === 1){
                if($filter['value'] === ""){
                    return true; //空
                }
                $fieldDB = "itinerary_group";
                $filter['code'] = $filter['value'];
            }
            else if($key === 2){
                if($filter['value'] === ""){
                    return false; //不可以空
                }
                $fieldDB = "itinerary_group";
                $filter['name'] = $filter['value'];
            }
            else{
                return response()->json(['error' => '請傳入有效查詢的欄位。']);
            }
        }
        else{
            return response()->json(['error' => '請傳入查詢的欄位或數值。']);
        }
        unset($filter['value']);
        unset($filter['fieldName']);
        if(array_key_exists('fieldId', $filter)){
            $fieldId = $filter['fieldId'];
            unset($filter['fieldId']);
        }

        $result = $this->requestService->aggregate_search( $fieldDB, null, $filter, $page=0);
        $result_data = json_decode($result->getContent(), true);

        if($result_data['count'] === 0){
            return true;
        }
        else if($result_data['count'] === 1){
            // 如果已建過此筆 可能為同一筆資料
            if($fieldId !== null && $result_data['docs'][0]['_id'] === $fieldId){
                return true;
            }
            else{ // 如果沒建過此筆 則為重複
                return false;
            }
        }else{
            return false;

        }
    }


}
