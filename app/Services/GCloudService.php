<?php

namespace App\Services;

use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Validation\Rule;
use Validator;

class GCloudService
{
    public function index(Request $request)
    {
        // https://medium.com/@pawanjotkaurbaweja/uploading-images-to-google-storage-using-laravel-d9a4bc15b8aa
        try
        {
            // Validate the request
            $rule = [
                'type' => ['required', 'string', Rule::in(['attractions', 'hotels', 'plays', 'restaurants', 'staffs', 'transportations', 'rooms', 'meals'])],
                'img' => 'required|image|mimes:jpeg,png,jpg|max:3072',
            ];
            
            $validator = Validator::make($request->all(), $rule);
            if($validator->fails()){
                return response()->json($validator->errors()->toJson(), 400);
            }

            $img = $request->file('img');
            $foldername = $validator->safe()->only('type');
            $foldername = $foldername['type'];
            $sub_filename = $img->getClientOriginalExtension();
            $file_name = uniqid().'.'.$sub_filename;

            
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
        $imgData = json_decode($request->getContent(), true);
        dd($imgData);
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