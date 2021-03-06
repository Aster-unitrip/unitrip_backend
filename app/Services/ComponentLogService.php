<?php
namespace App\Services;

class ComponentLogService
{

    public function checkPrivateToPublic($data)
    {
        // check components_log 表
        $filter['action'] = 'private2public';
        $filter["source_company"] = auth()->user()->company_id;
        $filter["target_company"] = $data['owned_by'];
        $filter["source_id"] = $data['_id'];

        return $filter;
    }

    public function checkPublicToPrivate($data)
    {

        $filter['action'] = 'public2private';
        // $filter["source_company"] = $data['owned_by'];
        $filter["target_company"] = auth()->user()->company_id;
        $filter["source_id"]= $data['_id'];

        return $filter;
    }

    public function isCreate($type, $data)
    {
        if($type === 'public'){
            $filter['action'] = 'create_public';
        }
        else if($type === 'private'){
            $filter['action'] = 'create_private';
        }
        $filter["target_company"] = auth()->user()->company_id;
        $filter["target_id"]= $data['_id'];

        return $filter;
    }

    public function recordPrivateToPublic($input, $insert_id, $data)
    {
        $add_log_private2public['type'] = $input['type'];
        $add_log_private2public['action'] = 'private2public';
        $add_log_private2public["source_company"] = auth()->user()->company_id;
        $add_log_private2public["target_company"] = auth()->user()->company_id;
        $add_log_private2public['source_id'] = $input['_id'];
        $add_log_private2public["target_id"]= $insert_id;
        $add_log_private2public["user_id"]= auth()->user()->id;
        $add_log_private2public["created_at"]= $data['created_at'];
        $add_log_private2public["updated_at"]= $data['updated_at'];


        return $add_log_private2public;
    }



    public function recordPublicToPrivate($input, $insert_id, $data)
    {
        $add_log_public2private['type'] = $input['type'];
        $add_log_public2private['action'] = 'public2private';
        $add_log_public2private["source_company"] = $input['source_company'];
        $add_log_public2private["target_company"] = auth()->user()->company_id;
        $add_log_public2private['source_id'] = $input['_id'];
        $add_log_public2private["target_id"]= $insert_id;
        $add_log_public2private["user_id"]= auth()->user()->id;
        $add_log_public2private["created_at"]= $data['created_at'];
        $add_log_public2private["updated_at"]= $data['updated_at'];


        return $add_log_public2private;
    }

    public function recordCreate($type, $data)
    {
        $add_log_create['type'] = $type;
        $add_log_create["source_company"] = null;
        $add_log_create["target_company"] = auth()->user()->company_id;
        $add_log_create["source_id"] = null;
        $add_log_create["target_id"]= $data["_id"];
        $add_log_create["user_id"]= auth()->user()->id;
        $add_log_create["created_at"]= $data['created_at'];
        $add_log_create["updated_at"]= $data['updated_at'];

        if($data['is_display'] === true) {
            $add_log_create['action'] = 'create_public';
        }else{
            $add_log_create['action'] = 'create_private';
        }
        return $add_log_create;
    }

    public function recordDelete()
    {

    }

    public function checkLogFilter($searchResultPrivateToPublic, $searchResultPublicToPrivate, $searchResultIsCreate){
        if(array_key_exists('count', $searchResultPrivateToPublic) && $searchResultPrivateToPublic['count'] === 0){
            if(array_key_exists('count', $searchResultPublicToPrivate) && $searchResultPublicToPrivate['count'] === 0){
                if(array_key_exists('count', $searchResultIsCreate) && $searchResultIsCreate['count'] === 0){
                    return true;
                }
            }
        }
        // TODO 評估是否有log
        return response()->json(['error' => 'You can not access this component.']);
    }
}
