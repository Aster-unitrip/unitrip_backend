<?php

namespace App\Http\Controllers;

use App\Services\GCloudService;
use Illuminate\Http\Request;

class ImgController extends Controller
{
    private $gCloudService;

    public function __construct(GCloudService $gCloudService)
    {
        // $this->middleware('auth');
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

    public function dmUpload(Request $request)
    {
        return $this->gCloudService->dm_upload($request);
    }

    public function dm_remove(Request $request)
    {
        return $this->gCloudService->dm_remove($request);
    }

    public function logo_upload(Request $request)
    {
        return $this->gCloudService->logo_upload($request);
    }

    public function logo_remove(Request $request)
    {
        return $this->gCloudService->logo_remove($request);
    }
}