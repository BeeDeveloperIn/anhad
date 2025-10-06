<?php

namespace App;

use App\Http\Traits\ActivityLogger;
use App\Http\Traits\AwsS3Bucket;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use AwsS3Bucket, ActivityLogger;
    
    protected $fillable = ['image', 'description', 'heading', 'link','status','font','text_color'];

    public function checkImage(){
        if ($this->checkFile()) {
            return $this->getUrl().$this->image;
        }
        return false;
    }
}
