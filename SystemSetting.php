<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['name', 'description', 'email', 'tel', 'address','dark_logo', 'logo', 'favicon', 'meta_keywords', 'meta_description', 'google_analytics', 'facebook_pixel','shipping_charge'];

    public function getRouteKeyName()
    {
    	return 'slug';
    }

    public function getImage($type){
        $url = '/frontend/img/';
        if($type == 1){
            $defaultImage = $url."logo.png";
            $img = $this->logo;
        }
        else if($type == 2)
        {
            $defaultImage = $url."logo-black.png";
            $img = $this->dark_logo;
        }
        else if($type == 3){
            $defaultImage = $url."SMALL-LOGO-1.png";
            $img = $this->favicon;
        }
        if(\Storage::disk('public')->exists($img)){
            $finalImage = $url.$img;
        }else{
            $finalImage = $defaultImage;
        }
        return $finalImage;
    }
}
