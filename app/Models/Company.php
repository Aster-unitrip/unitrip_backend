<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'tax_id',
        'tel',
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
        'account_number'
    ];
}
