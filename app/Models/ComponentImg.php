<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentImg extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_type',
        'component_id',
        'img_id'
    ];
}
