<?php
namespace App\Services;

class ComponentLogService
{

    public function checkPrivateToPublic($data)
    {

        $filter['action'] = 'private2public';
        $filter["source_company"] = auth()->user()->company_id;
        $filter["target_company"] = $data['owned_by'];
        $filter["source_id"] = $data['_id'];

        return $filter;
    }

    public function checkPublicToPrivate($data)
    {

        $filter['action'] = 'public2private';
        $filter["source_company"] = $data['owned_by'];
        // $filter["target_company"] = auth()->user()->company_id;
        $filter["target_id"]= $data['_id'];

        return $filter;
    }

    public function recordPrivateToPublic($type, $data)
    {
        $add_log_private2public['type'] = $type;
        $add_log_private2public['action'] = 'public2private';
        $add_log_private2public["source_company"] = $data['owned_by'];
        $add_log_private2public["target_company"] = auth()->user()->company_id;
        $add_log_private2public["target_id"]= $data['_id'];

        return $add_log_private2public;
    }



    public function recordPublicToPrivate()
    {

    }

    public function recordCreate()
    {

    }

    public function recordDelete()
    {

    }

    public function checkLogFilter($searchResultPrivateToPublic, $searchResultPublicToPrivate){
        if(array_key_exists('count', $searchResultPrivateToPublic) && $searchResultPrivateToPublic['count'] === 0){
            if(array_key_exists('count', $searchResultPublicToPrivate) && $searchResultPublicToPrivate['count'] === 0){
                return true;
            }
            else{
                return response()->json(['error' => 'You can not access this component(PublicToPrivate).']);
            }
        }
        else{
            return response()->json(['error' => 'You can not access this component(PrivateToPublic).']);
        }
    }
}
