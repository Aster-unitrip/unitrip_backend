<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use App\Services\CompanyService;

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
        $validator = Validator::make($request->all(), [
            'contact_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'contact_tel' => 'required|string|min:8,12',
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
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        try{
            $user = User::create(array_merge(
                $validator->validated(),
                ['password' => bcrypt($request->password)]
            ));

            $company = Company::create(
                $validator->validated()
            );

            $companyUser = CompanyUser::create(
                    ['user_id' => $user->id, 'company_id' => $company->id]
            );
        }
        catch(\Exception $e){
            if ($user) {
                $user->delete();
            }
            if ($company) {
                $company->delete();
            }
            if ($companyUser) {
                $companyUser->delete();
            }
            return response()->json(['error' => $e->getMessage()], 400);
        }
        
        return response()->json([
            'message' => 'User successfully registered',
            // 'user' => array_merge($user, $company) 
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
        $profile['company'] = $this->companyService->getById($profile->id);
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
    public function findCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'contact_tel' => 'required|string|min:8,12',
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
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        try{
            $user = $this->userService->update(
            // $user = User::update(array_merge(
                $validator->validated(),
                ['password' => bcrypt($request->password)]
            );

            $company = $this->companyService->update(
                $validator->validated()
            );

            // $company = Company::update(
            //     $validator->validated()
            // );

            $companyUser = CompanyUser::create(
                    ['user_id' => $user->id, 'company_id' => $company->id]
            );
        }
        catch(\Exception $e){
            if ($user) {
                $user->delete();
            }
            if ($company) {
                $company->delete();
            }
            if ($companyUser) {
                $companyUser->delete();
            }
            return response()->json(['error' => $e->getMessage()], 400);
        }
        
        return response()->json([
            'message' => 'User successfully registered',
            // 'user' => array_merge($user, $company) 
        ], 201);
    }

}