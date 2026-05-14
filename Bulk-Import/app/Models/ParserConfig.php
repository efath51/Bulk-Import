<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParserConfig extends Model
{
    protected $fillable = ['name', 'pattern', 'is_active'];
}
