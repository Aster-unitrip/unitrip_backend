<?php

namespace App\Services;

use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;

class GCloudService
{
    public function index(Request $request, $foldername)
    {
        // https://medium.com/@pawanjotkaurbaweja/uploading-images-to-google-storage-using-laravel-d9a4bc15b8aa
        $storage = new StorageClient();
        $bucket = $storage->bucket('unitrip_components');

        $img = $request->file('photo');
        $googleCloudStoragePath = $foldername.'/'.'raw'.'/'.$img->getClientOriginalName();
        $bucket->upload(file_get_contents($img), [
            'name' => $googleCloudStoragePath,
        ]);

        return response()->json([
            "status" => "success",
            "message" => "Image saved successfully ",
            "data" => [
                // "url" => url($googleCloudStoragePath),
                "url" => 'https://storage.googleapis.com/unitrip_components/'.$googleCloudStoragePath

                ]
            ]);
    }
}