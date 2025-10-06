<?php

namespace App;

use App\Http\Traits\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ShippingOption extends Model
{
    use ActivityLogger;
    protected $fillable = ['name', 'url'];
}
