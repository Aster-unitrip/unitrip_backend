<?php

namespace App\Services;



class RequestService
{
    public static function get_all($filter=[], $page=0)
    {
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/find";
        $limit = 10;
        $data = array(
            "collection" => "attractions",
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "projection" => array(
                "_id" => 1,
                "address_city" => 1,
                "address_town" => 1,
                "name" => 1,
            ),
            "sort" => array("_id" => 1),
            "limit" => 10,
            "skip" => $page * $limit,
        );
        if ($filter != []) {
            $data['filter'] = $filter;
        }
        $postdata = json_encode($data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-type:application/json',
                    'Access-Control-Request-Headers: *',
                    'api-key:'.config('app.mongo_key'),
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $http_code = explode(' ', $http_response_header[0])[1];

            if ($http_code == "200") {
                $result = json_decode($result, true);
                return array($result['documents'], 200);
            } else {
                return array($result, 400);
            }
            
        }
        catch(\Exception $e) {
            return array($e->getMessage(), 400);
        }
    }

    /**
    * 傳送post請求
    * @param string $url 請求地址
    * @param array $post_data post鍵值對資料
    * @return string
    */
    public function insert_one($post_data) {
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/insertOne";
        $data = array(
            "collection" => "attractions",
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "document" => $post_data
        );
        $postdata = json_encode($data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-type:application/json',
                    'Access-Control-Request-Headers: *',
                    'api-key:'.config('app.mongo_key'),
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $http_code = explode(' ', $http_response_header[0])[1];

            if ($http_code == "200") {
                $result = json_decode($result, true);
                return array($result['documents'], 200);
            } else {
                return array($result, 400);
            }
            
        }
        catch(\Exception $e) {
            return array($e->getMessage(), 400);
        }
        
    }

    public function get_one($id)
    {   
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/findOne";
        $data = array(
            "collection" => "attractions",
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => array(
                "_id" => array( "\$oid" => $id )
            ),
        );
        $postdata = json_encode($data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-type:application/json',
                    'Access-Control-Request-Headers: *',
                    'api-key:'.config('app.mongo_key'),
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $http_code = explode(' ', $http_response_header[0])[1];

            if ($http_code == "200") {
                $result = json_decode($result, true);
                return array($result['document'], 200);
            } else {
                return array($result, 400);
            }
            
        }
        catch(\Exception $e) {
            return array($e->getMessage(), 400);
        }
    }

    public function update($update_data)
    {   
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/replaceOne";
        $id = $update_data['_id'];
        unset($update_data['_id']);
        $data = array(
            "collection" => "attractions",
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => array(
                "_id" => array( "\$oid" => $id )
            ),
            "replacement" => $update_data,
            "upsert" => false
        );
        $postdata = json_encode($data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-type:application/json',
                    'Access-Control-Request-Headers: *',
                    'api-key:'.config('app.mongo_key'),
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $http_code = explode(' ', $http_response_header[0])[1];

            if ($http_code == "200") {
                $result = json_decode($result, true);
                return array("Modified ID: ".$id, 200);
            } else {
                return array($result, 400);
            }
            
        }
        catch(\Exception $e) {
            return array($e->getMessage(), 400);
        }
    }
}