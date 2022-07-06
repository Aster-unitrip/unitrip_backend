<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\CompanyService;
use App\Services\UserService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;
use App\Notifications\Activate_account;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Validation\ValidationException;
use App\Services\RequestPService;
use Illuminate\Support\Facades\DB;



use Validator;


class AuthController extends Controller
{
    private $companyService;
    private $userService;

    use ThrottlesLogins;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(CompanyService $companyService, UserService $userService, RequestPService $requestPService)
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh', 'register_data']]);
        $this->requestService = $requestPService;
        $this->companyService = $companyService;
        $this->userService = $userService;
        $this->supplierRegisterRule = [
            'company_type' => ['required', 'integer', Rule::in([1,2])],
            'contact_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|confirmed|min:8',
            'contact_tel' => 'nullable|string|min:8,12',
            'contact_tel_extension' => 'nullable|string',
            'job_title' => 'nullable|string|max:25', // 供應商 窗口職稱 / 旅行社 使用者職稱
            'contact_address_city' => 'string|max:5',
            'contact_address_town' => 'string|max:5',
            'contact_address' => 'string|max:30',
            'role_id' => 'nullable|string|min:1',
            'title' => 'required|string|max:20',
            'tax_id' => 'required|string|max:8',
            'fax' => 'string|max:15',
            'tel' => 'required|string|max:15',
            'address_city' => 'required|string|max:5',
            'address_town' => 'required|string|max:5',
            'address' => 'required|string|max:30',
            'logo_path' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:150',
            'owner' => 'required|string|max:10',
            'intro' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:20',
            'bank_code' => 'nullable|string|max:5',
            'account_name' => 'nullable|string|max:10',
            'account_number' => 'nullable|string|max:20',
            // 'ta_register_num' => 'nullable|string|max:6',
            // 'ta_category' => 'nullable|string|max:20',
        ];

        $this->agencyRegisterRule = $this->supplierRegisterRule + array(
            'ta_register_num' => 'required|string|max:6',
            'ta_category' => 'required|string|max:10',
            'tqaa_num' => 'required|string|max:5', //品保
            'travel_agency_name' => 'required|string|max:50' //旅行社名稱
        );

        $this->updateRule = [
            'id' => 'required|integer',
            'contact_name' => 'required|string|between:2,100',
            'contact_tel' => 'required|string|min:8,12',
            'role_id' => 'required|string|min:1',
            'email' => 'required|string|email|max:100',
            'job_title' => 'nullable|string|max:25', // 供應商 窗口職稱 / 旅行社 使用者職稱
            //'password' => 'required|string|confirmed|min:8',
            'address_city' => 'string|max:5',
            'address_town' => 'string|max:5',
            'address' => 'string|max:30',
            'company_id' => 'required|integer',
            'company.id' => 'required|integer',
            'company.title' => 'required|string|max:20',
            'company.tax_id' => 'required|string|max:12',
            'company.fax' => 'string|max:15',
            'company.tel' => 'required|string|max:15',
            'company.address_city' => 'required|string|max:5',
            'company.address_town' => 'required|string|max:5',
            'company.address' => 'required|string|max:30',
            'company.logo_path' => 'required|string|max:100',
            'company.website' => 'nullable|string|max:150',
            'company.owner' => 'required|string|max:10',
            'company.intro' => 'nullable|string|max:255',
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

    public function login(Request $request)
    {
        $this->validateLogin($request);

        //check if the user has too many login attempts.
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)){
            //Fire the lockout event.
            $this->fireLockoutEvent($request);

            //redirect the user back after lockout.
            Log::warning('User failed to login too many times.', ['id' => $request->email]);
            return $this->sendLockoutResponse($request);
        }

        $email = $request->email;
        $password = $request->password;

        //attempt login.
        if ($token = Auth::attempt(['email' => $email,'password' => $password])) {
            Log::info('User logged in', ['id' => auth()->user()->email]);
            return $this->createNewToken($token);
        }
        else{
            //keep track of login attempts from the user.
            $this->incrementLoginAttempts($request);
            Log::info('User failed to login', ['id' => $request->email]);
            // return response()->json($request->errors(), 422);
            return $this->sendFailedLoginResponse($request);
        }
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $company_type = $request->all()['company_type'];
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
        $input_user['role_id'] = $validator->validated()['role_id']??"1";
        $input_user['email'] = $validator->validated()['email'];
        $input_user['password'] = $validator->validated()['password'];
        $input_user['contact_tel'] = $validator->validated()['contact_tel'];
        $input_user['contact_tel_extension'] = $validator->validated()['contact_tel_extension'];
        $input_user['address_city'] = $validator->validated()['address_city'];
        $input_user['address_town'] = $validator->validated()['address_town'];
        $input_user['address'] = $validator->validated()['address'];
        $input_user['job_title'] = $validator->validated()['job_title'];

        $if_company_exists = $this->companyService->getCompanyByTaxId($validator->validated()['tax_id']);
        $if_user_exist = $this->userService->getUserByEmail($validator->validated()['email']);

        if($if_company_exists){
            return response()->json(['error' => "Company already exist, please login first."], 400);
        }
        else if($if_user_exist){
            return response()->json(['error' => 'User already exists'], 400);
        }
        else if(!$if_company_exists && !$if_user_exist){
            DB::beginTransaction();
            try{
                $company = $this->companyService->create($validator->validated());
                $company_id = $company->id;
                $input_user['company_id'] = $company_id;
                $user = User::create(array_merge(
                    $input_user,
                    ['password' => bcrypt($request->password)]
                ));
                DB::commit();
                // Send verify email
                // https://stackoverflow.com/questions/65285530/laravel-8-rest-api-email-verification
                // event(new Registered($user));
                // event(new Activate_account($user));
                $user->notify(new Activate_account($user));


                Auth::login($user);
            }
            catch(\Exception $e){
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        Log::info('User registered', ['id' => $input_user['email']]);
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
        // return $this->createNewToken(auth()->refresh());
        return $this->respondWithToken(auth()->refresh());
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
     * Get the opendata_travel_agencies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register_data($com_no) {

        // 先去mongo找
        $travel_agencies = $this->requestService->find_one('opendata_travel_agencies', null, 'com_no', $com_no);
        if(!$travel_agencies){
            return response()->json(['error' => "沒有這筆公司資訊"]);
        }
        else{
            // 更改名稱對照sql
            $result = $this->old_data_to_register_field($travel_agencies["document"]);
            return $result;
        }


    }

    public function old_data_to_register_field($old_data) {
        $tra_data['ta_category'] = $old_data['ta_category'];
        $tra_data['travel_agency_name'] = $old_data['c_name'];
        $tra_data['ta_register_num'] = $old_data['com_no'];
        // 另外區分address
        list($address_city, $address_town, $address) = explode(",", $old_data['address']);
        $tra_data['address_city'] = $address_city;
        $tra_data['address_town'] = $address_town;
        $tra_data['address'] = $address;
        $tra_data['tel'] = $old_data['tel'];
        $tra_data['fax'] = $old_data['fax'];
        $tra_data['owner'] = $old_data['manager'];
        $tra_data['website'] = $old_data['website'];
        $tra_data['title'] = $old_data['formal_name'];
        return $tra_data;
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
        if ($company_type == 2)
        {
            $rule['company.ta_register_num'] = 'required|string|max:6';
            $rule['company.ta_category'] = 'required|string|max:2';
        }

        $validator = Validator::make(json_decode($request->getContent(), true), $this->updateRule);
        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        $validated = $validator->validated();
        // $validated['password'] = bcrypt($request->password);
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
            // if ($user) {
            //     $user->delete();
            // }
            // if ($company) {
            //     $company->delete();
            // }
            // return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'User successfully updated',
        ], 201);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

/**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    public function username()
    {
        return 'email';
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
                    ? new JsonResponse([], 204)
                    : redirect()->intended($this->redirectPath());
    }

}
