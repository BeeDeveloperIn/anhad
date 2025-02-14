<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use ImageOptimizer;
use Digikraaft\ReviewRating\Traits\HasReviewRating;
use Carbon\Carbon;

class Product extends Model
{
    use HasReviewRating;

    public static $URL = 'frontend/img/products/';

    protected $fillable = [
        'name',
        'category_id', 
        'sub_category_id',
        'child_sub_category_id', 
        'description',
        'code', 
        'image', 
        'slug', 
        'price', 
        'selling_price',
        'meta_keywords', 
        'meta_description',
        'on_sale',
        'is_new',
        'is_featured',
        'shipping',
        'notes',
        'quantity',
        'is_latest',
        'is_accessories',
        'created_at'
    ];
    /**
    * change key from id to slug
    * @param $slug
    *
    */
    public function getRouteKeyName()
    {
    	return 'slug';
    }

    public function category()
    {
    	return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
    public function childsubCategory()
    {
        return $this->belongsTo(ChildSubCategory::class,'child_sub_category_id','id');
    }

    public function transactions()
    {
    	return $this->hasMany(Transaction::class);
    }

    public function inStock()
    {
        return $this->quantity > 0;
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function productSize()
    {
        return $this->hasMany(ProductSize::class);
    }
   
    public function sizes()
    {
    	return $this->belongsToMany(Size::class)->withPivot('quantity','is_active');
    }
    
    public function activeSizes()
    {
    	return $this->belongsToMany(Size::class)->orderBy('position','ASC')->where('product_size.is_active',1)->withPivot('quantity','is_active');
    }

    public function sale()
    {
    	return $this->hasMany('App\Sale');
    }

    public function checkPrice(){ 
        if($this->selling_price <= 0){
            return false;
        }
        return true;
    }

    public function setSellingPriceAttribute($value)
    {
        $this->attributes['selling_price'] = $value ?? 0.00;
    }

    public function getUrl($thumbnail = false){
        if($thumbnail){
            return self::$URL.$this->code."/thumbnail";
        }
        return self::$URL.$this->code;
    }

    public function getImage($img = NULL,$thumbnail= true){
        $finalImage = "/frontend/img/no-image.png";
        $cacheKey = 'product_image'.$this->id;
        if($thumbnail){
            $cacheKey .= 'thumbnail';
        }
        if(!is_null($img)){
            $cacheKey .= $img;
        } 

        //caching for product image
        if(Cache::has($cacheKey)) {
            $finalImage = Cache::get($cacheKey);
        }else{
            if($this->photos->count()){
                $cacheKey = 'product_image'.$this->id;
                $url = $this->getUrl($thumbnail).'/';
                if($thumbnail){
                    $cacheKey .= 'thumbnail';
                }
                $image =  !is_null($img) ? $url.$img :$url.$this->photos->first()->images;
                if(!is_null($img)){
                    $cacheKey .= $img;
                }                
                if(file_exists($image)){
                    $finalImage = $image;
                    Cache::put($cacheKey, $finalImage, 60 * 24); // Cache for 24 hours
                    $this->setCache($cacheKey);
                }
            }
        }
        return $finalImage;
    }
    
    public function checkImage($img = NULL){
        if($this->photos->count()){
            $image = $this->getUrl().'/'.$this->photos->first()->images;
            if(!is_null($img)){
                $image = $this->getUrl().'/'.$img;
            }
            if(file_exists($image)){
                return true;
            }
        }
        return false;
    }

    public static function formatPrice($price) {
         // Ensure the price is a number
        if (!is_numeric($price)) {
            return false;
        }

        // Convert price to float
        $price = (float)$price;

        // Truncate to 2 decimal places without rounding
        $price = floor($price * 100) / 100;

        // Format the result to 2 decimal places
        return number_format($price, 2, '.', '');
    }

    public function setCache($cacheKey){
        if(Cache::has('product_images')){
            $arr = Cache::get('product_images');
            if(!in_array($cacheKey,$arr))
                $arr = array_merge($arr,[$cacheKey]);
        }else{
            $arr = [$cacheKey]; 
        }
        Cache::put('product_images',$arr,60*24);
    }

    public function checkStock(){
        if($this->is_accessories){
            return $this->quantity > 0?false:true;
        }else{
            if (isset($this->sizes) && $this->sizes->sum('pivot.quantity') > 0){
                return false;
            }
        }
        return true;
    }

    public function averageRating(?int $round = 2, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        return $this->reviews()
            ->when($from && $to, function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [
                    $from->toDateTimeString(),
                    $to->toDateTimeString(),
                ]);
            })
            ->when($round, function ($query) use ($round) {
                $query->selectRaw("ROUND(AVG(rating), $round) as rating");
            }, function ($query) {
                $query->selectRaw("AVG(rating) as rating");
            })
            // ->where('status',1)
            ->value('rating');
    }
}
