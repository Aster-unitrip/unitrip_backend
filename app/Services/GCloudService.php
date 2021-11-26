<?php

namespace App\Services;

use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Validation\Rule;

class GCloudService
{
    public function index(Request $request)
    {
        // https://medium.com/@pawanjotkaurbaweja/uploading-images-to-google-storage-using-laravel-d9a4bc15b8aa
        try
        {
            // Validate the request
            $rule = [
                'type' => ['required', 'string', Rule::in(['attraction', 'hotel', 'play', 'restaurant', 'staff', 'transportation', 'room', 'meal'])],
                'img' => 'required|image|mimes:jpeg,png,jpg|size:3072',
            ];
            $validator = Validator::make($request->all(), $rule);

            $img = $validator->safe()->file('img');
            $foldername = $validator->safe()->input('type');
            $sub_filename = explode('.', $img->getClientOriginalName());
            $file_name = uniqid().'.'.$sub_filename[1];

            
            // Upload images to Google Cloud Storage
            $storage = new StorageClient();
            $bucket = $storage->bucket('unitrip_components');
            $googleCloudStoragePath = $foldername.'/'.'raw'.'/'.$file_name;
            $bucket->upload(file_get_contents($img), [
                'name' => $googleCloudStoragePath,
            ]);

            return array(
                'status' => 'success',
                'message' => 'image successfully saved. ',
                'data' => [
                    'url' => 'https://storage.googleapis.com/unitrip_components/'.$googleCloudStoragePath,
                ]
                );
        }
        catch (\Exception $e)
        {
            return $e->getMessage();
        }
        
    }

    public function removeImg(Request $request)
    {
        // {
        //     'type': 'attraction',
        //     'filename': 'xxxxxxxx'
        // }
        $imgData = $request->getContent();

        // Delete object
        $storage = new StorageClient();
        $bucket = $storage->bucket('unitrip_components');
        $googleCloudStoragePath = $imgData['type'].'/'.'raw'.'/'.$imgData['filename'];
        $object = $bucket->object($googleCloudStoragePath);
        try
        {
            $object->delete();
            return response()->json([
                "status" => "success",
                "message" => "image successfully deleted. ",
                "data" => [
                    'url' => 'https://storage.googleapis.com/unitrip_components/'.$googleCloudStoragePath,
                ]
            ]);
        }
        catch (\Exception $e)
        {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ]);
        }

    }
}