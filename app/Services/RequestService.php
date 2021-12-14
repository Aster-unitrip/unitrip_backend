<?php

namespace App\Services;



class RequestService
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
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/insertOne";
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
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/findOne";
        $data = array(
            "collection" => $collection,
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
        return $this->send_req($options, $url);
    }

    public function update($collection, $update_data)
    {   
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/replaceOne";
        $id = $update_data['_id'];
        unset($update_data['_id']);
        $update_data['updated_at'] = date('Y-m-d H:i:s');
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "filter" => array(
                "_id" => array( "\$oid" => $id )
            ),
            "replacement" => $update_data,
            "upsert" => false
        );
        // dd($data);
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

    public function aggregate_filter($collection, $projection, $company_id, $filter=[], $page=0, $query_private=false)
    {
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/aggregate";
        $limit = 10;
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "pipeline" => array(),
        );
        if ($filter != []) {
            $data['pipeline'][0]['$match'] = $filter;
            if (array_key_exists('categories', $filter) && gettype($filter['categories']) == 'array') {
                $data['pipeline'][0]['$match']['categories'] = array('$in' => $filter['categories']);
                // array_unshift($data['pipeline'], array('$match' => array('categories' => array('$in' => $filter['categories']))));
                // $data['pipeline'][0]['$match']['categories'] = ['$in' => $filter['categories']];
            }
        }
        if ($query_private) {
            array_push($data['pipeline'], array('$lookup' => array(
                'from' => $collection.'_private',
                'localField' => '_id',
                'foreignField' => 'public_attraction_id',
                'as' => 'private'
            )));
            array_push($data['pipeline'], array('$unwind'=>array('path'=>'$private', 'preserveNullAndEmptyArrays'=>true)));
        }
        if ($query_private) {
            array_push($data['pipeline'], array('$match' => array('private.owned_by' => $company_id)));
        }
        if ($projection != []) {
            array_push($data['pipeline'], array('$project' => $projection));
        }
        if ($page>0) {
            array_push($data['pipeline'], array('$skip' => $page*$limit));
        }
        array_push($data['pipeline'], array('$limit' => $limit));
        array_push($data['pipeline'], array(
            '$group' => array(
                '_id' => null,
                'docs' => array(
                    '$push' => '$$ROOT'
                ),
                'count' => array('$sum' => 1)
            )
        ));
        array_push($data['pipeline'], array('$project' => array('_id' => 0)));
        // dd($data);
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

    public function aggregate_facet($collection, $projection, $company_id, $filter=[], $page=0, $query_private=false)
    {
        $url = "https://data.mongodb-api.com/app/data-ruata/endpoint/data/beta/action/aggregate";
        $limit = 10;
        $data = array(
            "collection" => $collection,
            "database" => "unitrip",
            "dataSource" => "RealmCluster",
            "pipeline" => null,
        );
        $query_filter = [];
        if ($filter != []) {
            array_push($query_filter, array('$match' => $filter));
            if (array_key_exists('categories', $filter) && gettype($filter['categories']) == 'array') {
                $query_filter[0]['$match']['categories'] = array('$in' => $filter['categories']);
            }
        }
        if ($query_private) {
            array_push($query_filter, array('$lookup' => array(
                'from' => $collection.'_private',
                'localField' => '_id',
                'foreignField' => 'public_attraction_id',
                'as' => 'private'
            )));
            array_push($query_filter, array('$unwind'=>array('path'=>'$private', 'preserveNullAndEmptyArrays'=>true)));
        }
        if ($query_private) {
            array_push($query_filter, array('$match' => array('private.owned_by' => $company_id)));
        }
        if ($projection != []) {
            array_push($query_filter, array('$project' => $projection));
        }
        if ($page>0) {
            array_push($query_filter, array('$skip' => $page*$limit));
        }
        array_push($query_filter, array('$limit' => $limit));
        
        // array_push($data['pipeline'], array('$project' => array('_id' => 0)));
        $second_query_filter = $query_filter;
        array_pop($second_query_filter);
        array_pop($second_query_filter);
        $second_query_filter[] = array('$count' => 'totalCount');
        $data['pipeline'] =array( array(
            '$facet' => array(
                'docs' => $query_filter,
                'count' => $second_query_filter
            ))
            );
        array_push($data['pipeline'], array('$unwind' => array('path' => '$count')));
        array_push($data['pipeline'], array('$set' => array('count' => '$count.totalCount')));

        
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


    public static function send_req($options, $url)
    {
        try{
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $http_code = explode(' ', $http_response_header[0])[1];
            if ($http_code == "200") {
                $result = json_decode($result, true);
                if (array_key_exists('documents', $result) && $result['documents'] != []) {
                    return response()->json($result['documents'][0], 200);             
                }
                elseif (array_key_exists('document', $result) && $result['document'] != []) {
                    return response()->json($result['document'], 200);
                }
                else{
                    return response()->json(array('docs' => [], 'count' => 0), 200);
                }
                
            } else {
                return response()->json(['error' => $result], 400);
            }   
        }
        catch(\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }
}