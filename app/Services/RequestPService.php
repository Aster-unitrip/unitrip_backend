<?php

namespace App\Services;

use function PHPUnit\Framework\isType;

class RequestPService
{
    public function get_all($collection, $projection, $filter=[], $page=0)
    {
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/find";
        $limit = 10;
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "projection" => $projection,
            "sort" => array("_id" => 1),
            "limit" => 10,
            "skip" => $page * $limit,
        );
        if ($filter != []) {
            $data['filter'] = $filter;
            if (array_key_exists('categories', $filter) && gettype($filter['categories']) == 'array') {
                $data['filter']['categories'] = ['$in' => $filter['categories']];
            }

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
        return $this->send_req($options, $url);
    }

    /**
    * 傳送post請求
    * @param string $url 請求地址
    * @param array $post_data post鍵值對資料
    * @return string
    */
    public function insert_one($collection, $post_data) {
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/insert_one";
        // $post_data['attraction_id'] = array(
        //     "_id" => array("\$oid" => $post_data['attraction_id'])
        // );
        $post_data['updated_at'] = date('Y-m-d H:i:s');
        $post_data['created_at'] = date('Y-m-d H:i:s');

        $data = array(
            "collection" => $collection,
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
        return $this->send_req($options, $url);

    }

    public function get_one($collection, $id)
    {
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/find_one";
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => array(
                "_id" => $id
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

        return $this->send_req($options, $url);
    }

    public function find_one($collection, $_id, $field_name, $field_data)
    {
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/find_one";
        if(!$_id){
            $data = array(
                "collection" => $collection,
                "database" => "unitrip",
                "dataSource" => "RealmCluster",
                "filter" => array(
                    $field_name => $field_data
                ),
            );
        }
        else{
            $data = array(
                "collection" => $collection,
                "database" => "unitrip",
                "dataSource" => "RealmCluster",
                "filter" => array(
                    "_id" => $_id ,
                    $field_name => $field_data
                ),
            );
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
                if (array_key_exists('documents', $result) && $result['documents'] != []) {
                    return $result;
                }
                elseif (array_key_exists('document', $result) && $result['document'] != []) {
                    return $result;
                }
                else{
                    return false;
                }
            }
            else {
                return false;
            }
        }
        catch(\Exception $e) {
            return false;
        }

    }

    public function update($collection, $update_data)
    {
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/update";
        $id = $update_data['_id'];
        unset($update_data['_id']);
        $update_data['updated_at'] = date('Y-m-d H:i:s');
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => array(
                "_id" => $id
            ),
            "replacement" => $update_data,
            "upsert" => false
        );
        //dd($data);
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
                return response()->json("Modified ID: ".$id, 200);
            } else {
                return response()->json($result, 400);
            }
        }
        catch(\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }

    public function update_one($collection, $update_data)
    {
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/update_one";
        $id = $update_data['_id'];
        unset($update_data['_id']);
        $update_data['updated_at'] = date('Y-m-d H:i:s');
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => array(
                "_id" => $id
            ),
            "update" => $update_data,
            "upsert" => false
        );
        //dd($data);
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
                return response()->json("Modified ID: ".$id, 200);
            } else {
                return response()->json($result, 400);
            }
        }
        catch(\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }

    /* 列出元件
        $collection: 查詢
        $projection: 要列出的欄位
        $company_id: 公司ID
        $filter: 查詢條件
        $page: 頁數，0為第一頁
        $query_private: 是否查詢私有元件
        分頁、排序、計算筆數、查詢子槽
    */
    public function aggregate_facet($collection, $projection, $company_id, $filter=[], $page=0, $query_private=false)
    {
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/aggregate";
        $limit = 10;
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "pipeline" => null,
        );
        $query_filter = [];
        // 用正規表達式查詢名稱
        if ($filter != []) {
            array_push($query_filter, array('$match' => $filter));
            if (array_key_exists('categories', $filter) && gettype($filter['categories']) == 'array') {
                $query_filter[0]['$match']['categories'] = array('$in' => $filter['categories']);
            }
            if (array_key_exists('name', $filter) && gettype($filter['name']) == 'string') {
                $query_filter[0]['$match']['name'] = array('$regex' => $filter['name']);
            }
        }

        // 查詢子槽
        if ($query_private) {
            array_push($query_filter, array('$lookup' => array(
                'from' => $collection.'_private',
                'localField' => '_id',
                'foreignField' => 'public_id',
                'as' => 'private'
            )));
            array_push($query_filter, array('$unwind'=>array('path'=>'$private', 'preserveNullAndEmptyArrays'=>true)));
            array_push($query_filter, array('$addFields' => array('private.owned_by' => array('$ifNull' => array('$private.owned_by', 0)))));
        }
        if ($query_private) {
            array_push($query_filter, array('$match' => array('private.owned_by' => array('$in' => array($company_id, 0)) )));
        }
        // 留下需要的欄位
        if ($projection != []) {
            array_push($query_filter, array('$project' => $projection));
        }
        $second_query_filter = $query_filter;

        // 分頁
        if ($page>0) {
            array_push($query_filter, array('$skip' => $page*$limit));
        }
        array_push($query_filter, array('$limit' => $limit));

        // array_push($data['pipeline'], array('$project' => array('_id' => 0)));
        // $second_query_filter = $query_filter;
        // array_pop($second_query_filter);
        // array_pop($second_query_filter);
        $second_query_filter[] = array('$count' => 'totalCount');
        $data['pipeline'] =array( array(
            '$facet' => array(
                'docs' => $query_filter,
                'count' => $second_query_filter
            ))
            );
        // 格式轉換
        array_push($data['pipeline'], array('$unwind' => array('path' => '$count')));
        array_push($data['pipeline'], array('$set' => array('count' => '$count.totalCount')));


        $postdata = json_encode($data);
        // 顯示 MongoDB 的查詢語法
        // dump($postdata);
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
        return $this->send_req($options, $url);
    }

    public function aggregate_search($collection, $projection, $filter=[], $page=0){
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/aggregate";
        $limit = 10;
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "pipeline" => null,
        );
        $query_filter = [];

        // 用正規表達式查詢名稱
        if ($filter != []) {
            array_push($query_filter, array('$match' => $filter));
        }

        // 留下需要的欄位
        if ($projection != []) {
            array_push($query_filter, array('$project' => $projection));
        }
        $second_query_filter = $query_filter;

        // 分頁
        if ($page>0) {
            array_push($query_filter, array('$skip' => $page*$limit));
        }
        array_push($query_filter, array('$limit' => $limit));

        $second_query_filter[] = array('$count' => 'totalCount');
        $data['pipeline'] =array( array(
            '$facet' => array(
                'docs' => $query_filter,
                'count' => $second_query_filter
            ))
            );
        // 格式轉換
        array_push($data['pipeline'], array('$unwind' => array('path' => '$count')));
        array_push($data['pipeline'], array('$set' => array('count' => '$count.totalCount')));


        $postdata = json_encode($data);
        //return $postdata;
        // 顯示 MongoDB 的查詢語法
        // dump($postdata);
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
        return $this->send_req($options, $url);
    }

    
    public function aggregate($collection, $filter=[]){
        $url = "https://fast-mongo-by4xskwu4q-de.a.run.app/aggregate";
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "pipeline" => null,
        );
        $query_filter = [];

        // 用正規表達式查詢名稱
        if ($filter != []) {
            array_push($query_filter, array('$match' => $filter));
        }

        $second_query_filter[] = array('$count' => 'totalCount');
        $data['pipeline'] =array( array(
            '$facet' => array(
                'docs' => $query_filter,
                'count' => $second_query_filter
            ))
        );

        // 格式轉換
        array_push($data['pipeline'], array('$unwind' => array('path' => '$count')));
        array_push($data['pipeline'], array('$set' => array('count' => '$count.totalCount')));


        $postdata = json_encode($data);
        //return $postdata;
        // 顯示 MongoDB 的查詢語法
        // dump($postdata);
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
        return $this->send_req($options, $url);
    }

    public static function send_req($options, $url)
    {
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            //dump($result);
            $http_code = explode(' ', $http_response_header[0])[1];
            if ($http_code == "200") {
                $result = json_decode($result, true);
                if (array_key_exists('documents', $result) && $result['documents'] != []) {
                    return response()->json($result['documents'], $http_code);
                }
                elseif (array_key_exists('document', $result) && $result['document'] != []) {
                    return response()->json($result['document'], $http_code);
                }
                else{
                    return response()->json(array('docs' => [], 'count' => 0), $http_code);
                }
            }
            elseif ($http_code == "201")
            {
                $result = json_decode($result, true);
                return response()->json($result, $http_code);

            }
            else {
                return response()->json(['error' => $result], 400);
            }
        }
        catch(\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }
}
