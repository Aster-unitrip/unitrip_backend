<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public function users(){
        return $this->hasMany('App\Models\User');
    }

    protected $fillable = [
        'title',
        'travel_agency_name',
        'tax_id',
        'tel',
        'fax',
        'address_city',
        'address_town',
        'address',
        'logo_path',
        'website',
        'owner',
        'intro',
        'bank_name',
        'bank_code',
        'account_name',
        'account_number',
        'company_type',
        'ta_register_num',
        'ta_category',
        'tqaa_num'
    ];

}
