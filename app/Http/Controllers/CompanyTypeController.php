<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyType;



class CompanyTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    
    public function index(){
        $companyTypes = CompanyType::all();
        return response()->json($companyTypes);
    }
}
