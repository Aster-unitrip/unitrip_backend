<?php

namespace App\Http\Controllers;

use App\Services\GCloudService;
use Illuminate\Http\Request;

class ImgController extends Controller
{
    private $gCloudService;

    public function __construct(GCloudService $gCloudService)
    {
        $this->middleware('auth');
        $this->gCloudService = $gCloudService;

    }

    public function index(Request $request)
    {
        return $this->gCloudService->index($request);
        // return response()->json([
        //     "status" => "success",
        //     "message" => "image successfully saved. ",
        //     "data" => [
        //         "url" => $img_url
        //         ]
        //     ]);
    }

    public function remove(Request $request)
    {
        return $this->gCloudService->removeImg($request);
    }
}