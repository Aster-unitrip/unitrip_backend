<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\CompanyService;
use App\Services\UserService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;


class AuthController extends Controller
{
    private $companyService;
    private $userService;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(CompanyService $companyService, UserService $userService) 
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
        $this->companyService = $companyService;
        $this->userService = $userService;
        $this->supplierRegisterRule = [
            'company_type' => ['required', 'integer', Rule::in([1,2])],
            'contact_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|confirmed|min:6',
            'contact_tel' => 'required|string|min:8,12',
            'contact_address_city' => 'string|max:5',
            'contact_address_town' => 'string|max:5',
            'contact_address' => 'string|max:30',
            'role_id' => 'required|string|min:1',
            'title' => 'required|string|max:20',
            'tax_id' => 'required|string|max:12',
            'tel' => 'required|string|max:15',
            'address_city' => 'required|string|max:5',
            'address_town' => 'required|string|max:5',
            'address' => 'required|string|max:30',
            'logo_path' => 'required|string|max:100',
            'website' => 'required|string|max:150',
            'owner' => 'required|string|max:10',
            'intro' => 'required|string|max:255',
            'bank_name' => 'required|string|max:20',
            'bank_code' => 'required|string|max:5',
            'account_name' => 'required|string|max:10',
            'account_number' => 'required|string|max:20',
        ];
        
        $this->agencyRegisterRule = array_push($this->supplierRegisterRule, array(
            'ta_register_num' => 'required|string|max:6',
            'ta_category' => 'required|string|max:20',
        ));

        $this->updateRule = [
            'id' => 'required|integer',
            'contact_name' => 'required|string|between:2,100',
            'contact_tel' => 'required|string|min:8,12',
            'role_id' => 'required|string|min:1',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|confirmed|min:6',
            'address_city' => 'string|max:5',
            'address_town' => 'string|max:5',
            'address' => 'string|max:30',
            'company_id' => 'required|integer',
            'company.id' => 'required|integer',
            'company.title' => 'required|string|max:20',
            'company.tax_id' => 'required|string|max:12',
            'company.tel' => 'required|string|max:15',
            'company.address_city' => 'required|string|max:5',
            'company.address_town' => 'required|string|max:5',
            'company.address' => 'required|string|max:30',
            'company.logo_path' => 'required|string|max:100',
            'company.website' => 'required|string|max:150',
            'company.owner' => 'required|string|max:10',
            'company.intro' => 'required|string|max:255',
            'company.bank_name' => 'required|string|max:20',
            'company.bank_code' => 'required|string|max:5',
            'company.account_name' => 'required|string|max:10',
            'company.account_number' => 'required|string|max:20',
        ];
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $company_type = $request->all()['company_type'];
        $rule = $this->registerRule;
        
        if ($company_type == 2)
        {
            $rule = $this->agencyRegisterRule;
        }
        else if ($company_type == 1)
        {
            $rule = $this->supplierRegisterRule;
        }
        $validator = Validator::make($request->all(), $rule);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        $input_user['contact_name'] = $validator->validated()['contact_name'];
        $input_user['role_id'] = $validator->validated()['role_id'];
        $input_user['email'] = $validator->validated()['email'];
        $input_user['password'] = $validator->validated()['password'];
        $input_user['contact_tel'] = $validator->validated()['contact_tel'];
        $input_user['address_city'] = $validator->validated()['address_city'];
        $input_user['address_town'] = $validator->validated()['address_town'];
        $input_user['address'] = $validator->validated()['address'];

        try{
            $if_company_exists = $this->companyService->getCompanyByTaxId($validator->validated()['tax_id']);
            if(!$if_company_exists){
                $company = $this->companyService->create($validator->validated());
                $company_id = $company->id;
            }else{
                $company_id = $if_company_exists->id;
            }

            $if_user_exist = $this->userService->getUserByEmail($validator->validated()['email']);
            if ($if_user_exist) {
                return response()->json(['error' => 'User already exists'], 400);
            }
            $input_user['company_id'] = $company_id;
            $user = User::create(array_merge(
                $input_user,
                ['password' => bcrypt($request->password)]
            ));

        }
        catch(\Exception $e){
            // if ($user) {
            //     $user->delete();
            // }
            // if ($company) {
            //     $company->delete();
            // }
            return response()->json(['error' => $e->getMessage()], 400);
        }
        
        return response()->json([
            'message' => 'User successfully registered',
        ], 201);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        $profile = auth()->user();
        $company_id = $profile->company_id;
        $profile['company'] = $this->companyService->getById($company_id);
        return response()->json($profile);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }


    /**
     * Update user profile and company profile.
     *
     * @param  integer  $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $company_type = $request->all()['company']['company_type'];
        $rule = $this->updateRule;
        if ($company_type == 2)
        {
            $rule['company.ta_register_num'] = 'required|string|max:6';
            $rule['company.ta_category'] = 'required|string|max:2';
        }

        $validator = Validator::make(json_decode($request->getContent(), true), $rule);
        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        $validated = $validator->validated();
        $validated['password'] = bcrypt($request->password);
        // unset($validated['password']);
        
        // Make sure the user is the owner of the company
        $currectCompanyId = $validated['company_id'];
        if ($currectCompanyId != $validated['company']['id']) {
            return response()->json(['error' => 'You are not the owner of this company'], 400);
        }

        try{
            // 總共兩個表要更新
            // User, Company
            $user = $this->userService->update(
                // $validator->validated(),
                $validated
            );

            $company = $this->companyService->update(
                // $validator->validated()
                $validated['company']
            );
        }
        catch(\Exception $e){
            if ($user) {
                $user->delete();
            }
            if ($company) {
                $company->delete();
            }

            return response()->json(['error' => $e->getMessage()], 400);
        }
        
        return response()->json([
            'message' => 'User successfully updated',
        ], 201);
    }

}