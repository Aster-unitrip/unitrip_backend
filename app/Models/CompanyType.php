<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
        
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
