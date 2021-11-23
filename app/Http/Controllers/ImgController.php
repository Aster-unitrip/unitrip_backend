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
        $this->gCloudService->index($request, 'attractions');
    }
}