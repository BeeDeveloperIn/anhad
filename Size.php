<?php

namespace App;

use App\Http\Traits\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use ActivityLogger;
    protected $fillable = ['name', 'slug','position'];

    public function products()
    {
    	return $this->belongsToMany(Product::class)->withPivot('quantity');
    }
}
