<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Validator;

class CompanyController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api');
    }

    /**
     * Register a new company
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:20',
            'tax_id' => 'required|string|max:12',
            'tel' => 'required|string|max:15|',
            'email' => 'required|email|string|max:100',
            'address_city' => 'required|string|max:5',
            'address_town' => 'required|string|max:5',
            'address' => 'required|string|max:30',
            'logo_path' => 'required|string|max:100',
            'website' => 'nullable|string|max:150',
            'owner' => 'required|string|max:10',
            'intro' => 'nullable|string|max:255',
            'bank_name' => 'required|string|max:20',
            'bank_code' => 'required|string|max:5',
            'account_name' => 'required|string|max:10',
            'account_number' => 'required|string|max:20',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $company = Company::create(array_merge(
                    $validator->validated()
                ));

        return response()->json([
            'message' => 'User successfully registered',
            'company' => $company
        ], 201);
    }


    public function update(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:20',
            'tax_id' => 'required|string|max:12',
            'tel' => 'required|string|max:15|',
            'email' => 'required|email|string|max:100',
            'address_city' => 'required|string|max:5',
            'address_town' => 'required|string|max:5',
            'address' => 'required|string|max:30',
            'logo_path' => 'required|string|max:100',
            'website' => 'nullable|string|max:150',
            'owner' => 'required|string|max:10',
            'intro' => 'nullable|string|max:255',
            'bank_name' => 'required|string|max:20',
            'bank_code' => 'required|string|max:5',
            'account_name' => 'required|string|max:10',
            'account_number' => 'required|string|max:20',
        ]);
    }

    
}