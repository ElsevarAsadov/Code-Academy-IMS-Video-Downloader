<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteFiles extends Model
{
    use HasFactory;

    protected $fillable = [
        'files'
    ];

    public $timestamps = false;

}
