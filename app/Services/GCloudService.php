<?php

namespace App\Services;

use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Validation\Rule;

class GCloudService
{
    public function index(Request $request, $foldername)
    {
        // https://medium.com/@pawanjotkaurbaweja/uploading-images-to-google-storage-using-laravel-d9a4bc15b8aa
        try
        {
            // Validate the request

            
            $rule = [
                'type' => ['required', 'string', Rule::in(['attraction', 'hotel', 'play', 'restaurant', 'staff', 'transportation', 'room', 'meal'])],
                'component_name' => 'required|string|size:20',
                'img' => 'requiredimage|mimes:jpeg,png,jpg|size:3072',
                'description' => 'nullable|string|max:50',
            ];
            $validator = Validator::make();

            $img = $validator->safe()->file('photo');
            $foldername = $request->input('type');
            
            // Upload images to Google Cloud Storage
            $storage = new StorageClient();
            $bucket = $storage->bucket('unitrip_components');
            $googleCloudStoragePath = $foldername.'/'.'raw'.'/'.$img->getClientOriginalName();
            $bucket->upload(file_get_contents($img), [
                'name' => $googleCloudStoragePath,
            ]);

            return 'https://storage.googleapis.com/unitrip_components/'.$googleCloudStoragePath;
        }
        catch (\Exception $e)
        {
            return $e->getMessage();
        }
        

        
    }
}