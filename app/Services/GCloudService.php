<?php

namespace App\Services;

use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Validation\Rule;
use Validator;
use Illuminate\Support\Facades\Log;

class GCloudService
{
    public function index(Request $request)
    {
        // https://medium.com/@pawanjotkaurbaweja/uploading-images-to-google-storage-using-laravel-d9a4bc15b8aa

        // Validate the request
        $rule = [
            'type' => ['required', 'string', Rule::in(['attractions', 'hotels', 'plays', 'restaurants', 'staffs', 'transportations', 'rooms', 'meals', 'accomentdations', 'activities'])],
            [
                'img.*' => 'required|mimes:jpg,jpeg,png|max:3072'
                ],[
                    'img.*.required' => 'Please upload an image',
                    'img.*.mimes' => 'Only jpeg, jpg and png formats are allowed',
                    'img.*.max' => 'Sorry! Maximum allowed size for an image is 3MB',
                ]
            // 'img.*' => 'required|mimes:jpeg,png,jpg|max:3072',
        ];
        $validator = Validator::make($request->all(), $rule);
        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $img = $request->file('img');
        $foldername = $validator->safe()->only('type');
        $foldername = $foldername['type'];
        $sub_filename = $img->getClientOriginalExtension();
        $file_name = uniqid().'.'.$sub_filename;

        try
        {
            // Upload images to Google Cloud Storage
            $storage = new StorageClient();
            $bucket = $storage->bucket('unitrip_components');
            $googleCloudStoragePath = $foldername.'/'.'raw'.'/'.$file_name;
            $bucket->upload(file_get_contents($img), [
                'name' => $googleCloudStoragePath,
            ]);
            Log::info('User successfully uploaded component image', ['user' => $request->email, 'img_url'=>'https://storage.googleapis.com/unitrip-dm/'.$googleCloudStoragePath]);
            return response()->json([
                "status" => "success",
                "message" => "image successfully saved. ",
                "data" => [
                    "url" => 'https://storage.googleapis.com/unitrip_components/'.$googleCloudStoragePath,
                    "filename" => $file_name
                ]
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }

    public function removeImg(Request $request)
    {
        // {
        //     'type': 'attraction',
        //     'filename': 'xxxxxxxx'
        // }
        $imgData = json_decode($request->getContent(), true);
        // Delete object
        $storage = new StorageClient();
        $bucket = $storage->bucket('unitrip_components');
        $googleCloudStoragePath = $imgData['type'].'/'.'raw'.'/'.$imgData['filename'];
        $object = $bucket->object($googleCloudStoragePath);
        try
        {
            $object->delete();
            Log::info('User successfully removed component image', ['user' => $request->email, 'img_url'=>$googleCloudStoragePath]);
            return response()->json([
                "status" => "success",
                "message" => "image successfully deleted: ".$imgData['filename'],
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 400);
        }

    }

    public function dm_upload(Request $request)
    {
        $rule = [

            [
                'img.*' => 'required|mimes:jpg,jpeg,png|max:3072'
                ],[
                    'img.*.required' => 'Please upload an image',
                    'img.*.mimes' => 'Only jpeg, jpg and png formats are allowed',
                    'img.*.max' => 'Sorry! Maximum allowed size for an image is 3MB',
                ]
            // 'img.*' => 'required|mimes:jpeg,png,jpg|max:3072',
        ];
        $validator = Validator::make($request->all(), $rule);
        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $img = $request->file('img');
        // 用公司 id 似乎不太直觀，但沒有公司英文名稱，且用公司中文名稱也不太好。只好先這樣將就
        $foldername = auth()->user()->company_id;
        $sub_filename = $img->getClientOriginalExtension();
        $file_name = uniqid().'.'.$sub_filename;

        try
        {
            // Upload images to Google Cloud Storage
            $storage = new StorageClient();
            $bucket = $storage->bucket('unitrip-dm');
            // gcs://unitrip-dm/1/raw/xxxxxxxx.jpg          原始檔
            // gcs://unitrip-dm/1/thumbnail/xxxxxxxx.jpg   縮小檔
            $googleCloudStoragePath = $foldername.'/'.'raw'.'/'.$file_name;
            $bucket->upload(file_get_contents($img), [
                'name' => $googleCloudStoragePath,
            ]);
            Log::info('User successfully uploaded dm image', ['user' => $request->email, 'img_url'=>'https://storage.googleapis.com/unitrip-dm/'.$googleCloudStoragePath]);
            return response()->json([
                "status" => "success",
                "message" => "image successfully saved. ",
                "data" => [
                    "url" => 'https://storage.googleapis.com/unitrip-dm/'.$googleCloudStoragePath,
                    "filename" => $file_name
                ]
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function dm_remove(Request $request)
    {
        $imgData = json_decode($request->getContent(), true);
        // Delete object
        $storage = new StorageClient();
        $bucket = $storage->bucket('unitrip-dm');
        $foldername = auth()->user()->company_id;
        $googleCloudStoragePath = $foldername.'/'.'raw'.'/'.$imgData['filename'];
        $object = $bucket->object($googleCloudStoragePath);
        try
        {
            $object->delete();
            Log::info('User successfully removed component image', ['user' => $request->email, 'img_url'=>$googleCloudStoragePath]);
            return response()->json([
                "status" => "success",
                "message" => "image successfully deleted: ".$imgData['filename'],
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 400);
        }

    }
}
