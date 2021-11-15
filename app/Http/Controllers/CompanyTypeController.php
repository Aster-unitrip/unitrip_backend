<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyType;


/**
 * @OA\Post(
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
 *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
 *     ),
 *     security={
 *         {"petstore_auth": {"write:pets", "read:pets"}}
 *     },
 *     @OA\RequestBody(
 *         description="Upload images request body",
 *         @OA\MediaType(
 *             mediaType="application/octet-stream",
 *             @OA\Schema(
 *                 type="string",
 *                 format="binary"
 *             )
 *         )
 *     )
 * )
 */
class CompanyTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * List all company types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(){
        $companyTypes = CompanyType::all();
        return response()->json($companyTypes);
    }
}
