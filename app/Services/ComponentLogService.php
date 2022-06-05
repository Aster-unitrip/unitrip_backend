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
        $filter["target_company"] = auth()->user()->company_id;
        $filter["target_id"]= $data['_id'];

        return $filter;
    }

    public function recordPrivateToPublic()
    {

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
}
