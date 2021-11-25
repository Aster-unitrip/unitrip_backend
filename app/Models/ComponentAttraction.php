<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentAttraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_id',
        'name',
        'website',
        'tel',
        'historic_level',
        'org_id',
        'categories',
        'address_city',
        'address_town',
        'address',
        'lng',
        'lat',
        'bussiness_time',
        'stay_time',
        'intro_summary',
        'description',
        'ticket',
        'memo',
        'parking',
        'attention',
        'experience',
        'is_display'
    ];

    
}
