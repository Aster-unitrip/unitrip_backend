<?php

namespace App\Services;



class RequestService
{
    public static function get_all($filter=null, $page=0)
    {
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/find";
        $limit = 10;
        $data = array(
            "collection" => "attractions",
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => $filter,
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
        $postdata = json_encode($data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-type:application/json',
                    'Access-Control-Request-Headers: *',
                    'api-key:4OTqpMbl4we6jdEQVGorUjwjzMIv4U68ACLM7LouZNXRVUV6DH6A6IPJ5DrtKD1Q',
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $result = json_decode($result, true);
            return $result['documents'];
        }
        catch(\Exception $e) {
            return $e->getMessage();
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
                    'api-key:4OTqpMbl4we6jdEQVGorUjwjzMIv4U68ACLM7LouZNXRVUV6DH6A6IPJ5DrtKD1Q',
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $result = json_decode($result, true);
            return $result['documents'];
        }
        catch(\Exception $e) {
            return $e->getMessage();
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
                    'api-key:4OTqpMbl4we6jdEQVGorUjwjzMIv4U68ACLM7LouZNXRVUV6DH6A6IPJ5DrtKD1Q',
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $result = json_decode($result, true);
            return $result['document'];
        }
        catch(\Exception $e) {
            return $e->getMessage();
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
                    'api-key:4OTqpMbl4we6jdEQVGorUjwjzMIv4U68ACLM7LouZNXRVUV6DH6A6IPJ5DrtKD1Q',
                ),
                'content' => $postdata,
                'timeout' => 10 // 超時時間（單位:s）
            )
        );
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $result = json_decode($result, true);
            return $result['documents'];
        }
        catch(\Exception $e) {
            return $e->getMessage();
        }
    }
}