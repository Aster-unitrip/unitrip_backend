<?php

namespace App\Services;

use App\Models\Company;

class CompanyService
{
    public function __construct(Company $company)
    {
        $this->model = $company;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function getById($id)
    {
        return $this->model->find($id);
    }

    public function getPublicDataById($id)
    {
        return $this->model->select('title', 'tel', 'logo_path', 'address_city', 'address_town', 'address', 'ta_register_num', 'tqaa_num')->find($id);
    }

    public function getCompanyByTaxId($taxId)
    {
        return $this->model->where('tax_id', $taxId)->first();
    }

    public function create($request)
    {
        return $this->model->create($request);
    }

    public function update($request)
    {
        $company = $this->model->find($request['id']);
        unset($request['id']);
        $company->update($request);
        return $company;
    }

    public function delete($id)
    {
        $company = $this->model->find($id);
        $company->delete();
    }
}