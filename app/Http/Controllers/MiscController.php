<?php

namespace App\Http\Controllers;

use App\Services\MiscService;

class MiscController extends Controller
{
    private $miscService;

    public function __construct(MiscService $miscService)
    {
        $this->middleware('auth');
        $this->miscService = $miscService;

    }

    /**
     * @OA\Get(
     *     path="/api/misc/city_town",
     *     tags={"misc"},
     *     description="取得縣市鄉鎮資料",
     *     operationId="cityTown",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *              @OA\AdditionalProperties(
     *                  type="integer",
     *                  format="int32"
     *              )
     *          ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid ID supplied"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Not Authorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
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
}