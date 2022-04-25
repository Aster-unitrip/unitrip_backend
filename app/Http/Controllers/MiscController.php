<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\MiscService;
use App\Models\User;
use App\Models\Company;


class MiscController extends Controller
{
    private $miscService;

    public function __construct(MiscService $miscService)
    {
        // $this->middleware('auth');
        $this->miscService = $miscService;

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
        //傳入要查詢的欄位、數值
        $filter = json_decode($request->getContent(), true);
        $filter['owned_by'] = auth()->user()->company_id;
        $company_data = Company::find($filter['owned_by']);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        if(array_key_exists('fieldName', $filter)){
            $array_field = array(0 => 'cus_group_code', 1 => 'code', 2 => 'name');
            $key = array_search($filter['fieldName'], $array_field);
            if($key === 0){
                $filter['db_name'] = 'cus_orders';
            }
            else if($key === 1 || $key === 2){
                $filter['db_name'] = 'itinerary_group';
            }
            else{
                return response()->json(['error' => '請傳入有效查詢的欄位。']);
            }
        }
        else{
            return response()->json(['error' => '請傳入查詢的欄位。']);
        }

        if(array_key_exists('value', $filter)){
            return $filter;

        }
        else{
            return response()->json(['error' => '請傳入查詢的數值。']);

        }
        //return $filter;
    }
}
