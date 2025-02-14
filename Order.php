<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Coupon;
use Illuminate\Notifications\Notifiable;

class Order extends Model
{
    use Notifiable;

    protected $guarded = [];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function products()
    {
    	return $this->belongsToMany(Product::class)->withPivot('quantity');
    }

    public function coupon()
    {
    	return $this->hasOne(Coupon::class,'code','billing_discount_code');
    }

    public function setBillingFullnameAttribute($value){
        $this->attributes['billing_fullname'] = preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $value);
    }

    public function orderProducts()
    {
    	return $this->hasMany(OrderProduct::class);
    }

    public function getOrderAddress(){
        $addr = [];
        if(!empty($this->billing_address)){
           $addr[] = $this->billing_address; 
        }
        if(!empty($this->billing_city)){
            $addr[] = $this->billing_city; 
        }
        if(!empty($this->billing_province)){
            $addr[] = $this->billing_province; 
        }
        if(!empty($this->billing_zipcode)){
            $addr[] = $this->billing_zipcode; 
        }
        return !empty($addr)?implode(', ',$addr):$this->billing_address;
    }

    public function getOrderBillingAddress(){
        $addr = [];
        if(!empty($this->billing_address_json)){
            $address = json_decode($this->billing_address_json);
            if(!empty($address->billing_fullname)){
                $addr[] = $address->billing_fullname; 
            }
            if(!empty($address->billing_email)){
                $addr[] = $address->billing_email; 
            }
            if(!empty($address->billing_phone)){
                $addr[] = $address->billing_phone; 
            }
            if(!empty($address->billing_address)){
                $addr[] = $address->billing_address; 
            }
            if(!empty($address->billing_city)){
                $addr[] = $address->billing_city; 
            }
            if(!empty($address->billing_province)){
                $addr[] = $address->billing_province; 
            }
            if(!empty($address->billing_zipcode)){
                $addr[] = $address->billing_zipcode; 
            }
            return !empty($addr)?implode(', ',$addr):$this->billing_address;
        }else{
            return $this->getOrderAddress();
        }
    }

    public function getOrderShippingAddress(){
        $addr = [];
        if(!empty($this->shipping_address_json)){
            $address = json_decode($this->shipping_address_json);
            
            if(!empty($address->shipping_address)){
                $addr[] = $address->shipping_address; 
            }
            if(!empty($address->shipping_city)){
                $addr[] = $address->shipping_city; 
            }
            if(!empty($address->shipping_province)){
                $addr[] = $address->shipping_province; 
            }
            return !empty($addr)?implode(', ',$addr):$this->billing_address;
        }else{
            return $this->getOrderBillingAddress();
        }
    }

    public function formatPrice($price) {
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

    public function setShippingDateAttribute($value)
    {
        $this->attributes['shipping_date'] = date('Y-m-d',strtotime($value));
    }

	public function getShippingDateAttribute($value)
    {
        return !is_null($value)?date('Y-m-d',strtotime($value)):'-';
    }

    public function fullPhoneNumber(){
        return (!empty($this->billing_country_code))?"+".$this->billing_country_code.' '.$this->billing_phone:$this->billing_phone;
    }

    public function getShippingData($type = 1){
        if(!empty($this->shipping_address_json)){
            $address = json_decode($this->shipping_address_json);
            if(!empty($address->shipping_fullname) && $type == 1){
                return $address->shipping_fullname; 
            }
            if(!empty($address->shipping_email) && $type == 2){
                return $address->shipping_email; 
            }
            if(!empty($address->shipping_phone) && $type == 3){
                return $address->shipping_phone; 
            }
            if(!empty($address->shipping_zipcode) && $type == 4){
                return $address->shipping_zipcode; 
            }
        }else{
            if($type == 1){
                return $this->billing_fullname; 
            }
            if($type == 2){
                return $this->billing_email; 
            }
            if($type == 3){
                return $this->billing_phone; 
            }
            if($type == 4){
                return $this->shipping_zipcode; 
            }
        }
    }

    public function getTotalAmount(){
        $total = $this->billing_discount+$this->billing_subtotal;
        return $this->formatPrice($total);
    }
}
