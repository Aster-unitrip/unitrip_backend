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
    
    /**
     * @OA\Get(
     *     path="/api/companies/types",
     *     tags={"company"},
     *     summary="List all company types",
     *     operationId="",
     *     @OA\Parameter(
     *         name="petId",
     *         in="path",
     *         description="ID of pet to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *              @OA\AdditionalProperties(
     *                  type="integer",
     *                  format="int32"
     *              )
     *          )
     *     ),
     *     security={
     *         {"petstore_auth": {"write:pets", "read:pets"}}
     *     },

     * )
     */
    public function index(){
        $companyTypes = CompanyType::all();
        return response()->json($companyTypes);
    }
}
