<?php

namespace App\Http\Controllers;

use App\Product;
use App\Order;
use App\OrderProduct;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;
use Razorpay\Api\Api;
use App\Coupon;
use App\SystemSetting;

class CheckoutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $razorpayKey;
    protected $razorpaySecret;

    public function __construct()
    {
        $this->razorpayKey = env('RAZORPAY_KEY');
        $this->razorpaySecret= env('RAZORPAY_SECRET');
    }

    public function index()
    {
        foreach (Cart::content() as $item) {
            $product = Product::findOrFail($item->model->id);
            if($product->is_accessories == 1){
                $quantity = $product->quantity;
            }else{
                $quantity = $product->sizes->find($item->options->size_id)->pivot->quantity;
            }
            $size = $item->options->size;
            if($item->qty > $quantity){
                return redirect()->back()->with('error', "$product->name only has $quantity quantity left for $size size. Please check and update the cart.");   
            }
        }
        $discount = session()->get('coupon')['discount'] ?? 0;
        $discountType = session()->get('coupon')['type'] ?? 0;
        $numbers = $this->getNumbers();
        $newSubtotal = isset(session()->get('coupon')['discount'])?Coupon::getCouponAmount():(Cart::subtotal(2,'.','') - $discount);;
        $newTotal = $newSubtotal;
        $shippingCost = $numbers->get('shippingCost');
       
        return view('checkout')->with([
            'discount' => Coupon::getCouponDiscount(),
            'newSubtotal' => $newSubtotal,
            'newTotal' => $newTotal,
            'shippingCost' => $shippingCost,
            'discountType' => $discountType 
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return 'create payment working';
    }

    public function userCouponStatusUpdate(){
        $coupon = session()->get('coupon');
        $user = auth()->user();
        if ($coupon && $user->is_new_user == 1 && $user->coupon_used == 0) {
            $couponExist = Coupon::where('code', $coupon['name'])
            ->where('is_active',1)->first();

            if($couponExist->first_use_valid && Cart::subtotal(2,'.','') >= $couponExist->min_amount){
                $user->coupon_used = 1;
                $user->is_new_user = 0;
                $user->update(); //Mark the coupon as used for this user
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'billing_fullname' => 'required',
            'billing_address' => 'required',
            'billing_city' => 'required',
            'billing_province' => 'required',
            'billing_zipcode' => 'required',
            'billing_phone' => 'required',
            'notes' => 'max:255',
        ]);
        $billing_address_json = [
            'billing_fullname' => $request->billing_fullname,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_province' => $request->billing_province,
            'billing_zipcode' => $request->billing_zipcode,
            'billing_iso_code' => $request->billing_iso_code??'in',
            'billing_country_code' => $request->billing_country_code??'91',
            'billing_phone' => $request->billing_phone,
            'billing_email' => $request->billing_email
        ];

        $shipping_address_json = [
            'shipping_fullname' => $request->shipping_fullname,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_province' => $request->shipping_province,
            'shipping_zipcode' => $request->shipping_zipcode,
            'shipping_iso_code' => $request->shipping_iso_code??'in',
            'shipping_country_code' => $request->shipping_country_code??'91',
            'shipping_phone' => $request->shipping_phone,
            'shipping_email' => $request->shipping_email
        ];

        $order_number  = date('Ymd').mt_rand(100000,999999);
        $data = [
            'razorpay_order_id' => $request->order_number,
            'user_id' => auth()->user()->id ?? null,
            'billing_discount' => $this->getNumbers()->get('discount'),
            'billing_discount_code' => $this->getNumbers()->get('code'),
            'billing_subtotal' => $this->getNumbers()->get('newSubtotal'),
            'billing_tax' => $this->getNumbers()->get('newTax'),
            'coupon_percent' => $this->getNumbers()->get('percent'),
            'billing_total' => $this->getNumbers()->get('newTotal'),
            'shipping_amount' => $this->getNumbers()->get('shippingCost')??null,
            'billing_fullname' => $request->billing_fullname,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_province' => $request->billing_province,
            'billing_zipcode' => $request->billing_zipcode,
            'billing_iso_code' => $request->billing_iso_code??'in',
            'billing_country_code' => $request->billing_country_code??'91',
            'billing_phone' => $request->billing_phone,
            'billing_email' => $request->billing_email,
            'payment_method' => $request->payment_method,
            'razorpay_id' => $request->razorpay_payment_id,
            'payment_method' => 'razorpay',
            'is_paid' => !is_null($request->razorpay_payment_id)?1:0,
            'notes' => $request->notes,
            'is_address_same' => $request->is_address_same,
            'status' => 'accepted',
            'billing_address_json' => json_encode($billing_address_json),
            'shipping_address_json' => json_encode($shipping_address_json),
            'error' => null,
        ];
        $order = Order::where(['razorpay_order_id' =>  $request->order_number])->first();
        if(!empty($order)){
            $order->update($data);
        }else{
            $data['order_number'] = $order_number;
            $order = Order::create($data);
        }

        // update user info if user is authenticated
        if(auth()->check()) {
            auth()->user()->update([
                'phone' => $request->billing_phone,
                'address' => $request->billing_address,
                'city' => $request->billing_city,
                'province' => $request->billing_province,
                'zipcode' => $request->billing_zipcode,
                'notes' => $request->notes
            ]);
        }
        foreach (Cart::content() as $item) {
            $orderProduct = OrderProduct::where([
                'order_id' => $order->id,
                'product_id' => $item->model->id,
                'size_id' => $item->options->size_id??NULL
            ])->first();
            $productData = [
                'order_id' => $order->id,
                'product_id' => $item->model->id,
                'quantity' => $item->qty,
                'size_id' => $item->options->size_id??NULL,
                'price' => $item->price
            ];
            if(!empty($orderProduct)){
                $orderProduct->update($productData);
            }else{
                OrderProduct::create($productData);
            }
            if($order->is_notified == 0){
                $product = Product::findOrFail($item->model->id);
                if($product->is_accessories == 1){
                    $quantity =  $product->quantity;
                    $product->update(['quantity' => $quantity-$item->qty]);
                }else{
                    $quantity = $product->sizes->find($item->options->size_id)->pivot->quantity ?? 0;
                    $product->sizes()->syncWithoutDetaching([$item->options->size_id => ['quantity' => $quantity-$item->qty]]);
                }
            }
        }

        if($order->is_notified == 0){
            //change notified status of order
            $order->update(['is_notified' => 1]);
            
            $this->sendOrderMail($order);
            $this->userCouponStatusUpdate();
            $this->sendOrderMail($order,1);
            $this->sendOrderNotification($order);
        }

        Cart::destroy();
        session()->forget('coupon');
        return response()->json([
            "success"=>true
        ]);
       // return redirect(route('my-orders.index'))->with('status', "Order Placed Successfully");
    }

    public function priceFormat($number) {
        // Convert the number to a string
        $numberStr = (string)$number;
        
        // Find the position of the decimal point
        $decimalPos = strpos($numberStr, '.');
        
        // If there is no decimal point, return the number as is
        if ($decimalPos === false) {
            return $numberStr;
        }
        
        // Limit the number to 2 decimal places
        $trimmedNumber = substr($numberStr, 0, $decimalPos + 3); // +3 to include 2 decimals and the dot
    
        return $trimmedNumber;
    }
    

    public function razorpaycheck(Request $request)
    {
        $newSubtotal = $this->priceFormat($this->getNumbers()->get('newSubtotal')*100);
        $api = new Api(env("RAZORPAY_KEY"),env("RAZORPAY_SECRET"));
        $receipt = 'ANDF_'.time();

        $rzpOrder =  $api->order->create(
            array(
                'receipt'=> $receipt,
                'amount' =>$newSubtotal,// $this->getNumbers()->get('newSubtotal') * 100,
                'currency' => 'INR'
            )
        );   
        $billing_address_json = [
            'billing_fullname' => $request->billing_fullname,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_province' => $request->billing_province,
            'billing_zipcode' => $request->billing_zipcode,
            'billing_iso_code' => $request->billing_iso_code??'in',
            'billing_country_code' => $request->billing_country_code??'91',
            'billing_phone' => $request->billing_phone,
            'billing_email' => $request->billing_email
        ];

        $shipping_address_json = [
            'shipping_fullname' => $request->shipping_fullname,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_province' => $request->shipping_province,
            'shipping_zipcode' => $request->shipping_zipcode,
            'shipping_iso_code' => $request->shipping_iso_code??'in',
            'shipping_country_code' => $request->shipping_country_code??'91',
            'shipping_phone' => $request->shipping_phone,
            'shipping_email' => $request->shipping_email
        ]; 
                    
        $order_number  = date('Ymd').mt_rand(100000,999999);
        $order = Order::create([
            'order_number' => $order_number,
            'razorpay_order_id' =>$rzpOrder->id,
            'user_id' => auth()->user()->id ?? null,
            'billing_discount' => $this->getNumbers()->get('discount'),
            'billing_discount_code' => $this->getNumbers()->get('code'),
            'billing_subtotal' => $this->getNumbers()->get('newSubtotal'),
            'billing_tax' => $this->getNumbers()->get('newTax'),
            'coupon_percent' => $this->getNumbers()->get('percent'),
            'billing_total' => $this->getNumbers()->get('newTotal'),
            'shipping_amount' => $this->getNumbers()->get('shippingCost')??null,
            'billing_fullname' => $request->billing_fullname,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_province' => $request->billing_province,
            'billing_zipcode' => $request->billing_zipcode,
            'billing_phone' => $request->billing_phone,
            'billing_email' => $request->billing_email,
            'razorpay_id' => null,
            'payment_method' => 'razorpay',
            'is_paid' => 0,
            'notes' => $request->notes,
            'is_address_same'=>$request->is_address_same,
            'status' => 'pending',
            'billing_address_json' => json_encode($billing_address_json),
            'shipping_address_json' => json_encode($shipping_address_json),
            'error' => null,
        ]);

        foreach (Cart::content() as $item) {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item->model->id,
                'quantity' => $item->qty,
                'size_id' => $item->options->size_id,
                'price' => $item->price
            ]);
        }
        return response()->json([
            "success"=>true,
            "order_number"=>$rzpOrder->id,
            'user_id'=>auth()->user()->id ?? null,
            'billing_discount'=>$this->getNumbers()->get('discount') ?? NULL,
            'billing_discount_code'=>$this->getNumbers()->get('code') ?? NULL,
            'billing_subtotal'=>$newSubtotal,//$this->getNumbers()->get('newSubtotal'),
            'billing_tax'=>$this->getNumbers()->get('newTax'),
            'billing_total'=>$newSubtotal,
            'notes' => $request->notes,
            'is_address_same' => $request->is_address_same,
            'billing_fullname' => $request->billing_fullname,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_iso_code' => $request->billing_iso_code,
            'billing_country_code' => $request->billing_country_code,
            'billing_province' => $request->billing_province,
            'billing_zipcode' => $request->billing_zipcode,
            'billing_phone' => $request->billing_phone,
            'billing_email' => $request->billing_email,
            'shipping_fullname' => $request->shipping_fullname,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_province' => $request->shipping_province,
            'shipping_zipcode' => $request->shipping_zipcode,
            'shipping_iso_code' => $request->shipping_iso_code??'in',
            'shipping_country_code' => $request->shipping_country_code??'91',
            'shipping_phone' => $request->shipping_phone,
            'shipping_email' => $request->shipping_email,
            'razorpay_key' => env("RAZORPAY_KEY")
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function getNumbers()
    {
        $setting = SystemSetting::first();
        $tax = 0;//config('cart.tax') / 100;
        $discount = isset(session()->get('coupon')['discount']) ?Coupon::getCouponDiscount(): 0;
        $code = session()->get('coupon')['name'] ?? null;
        $discountType = session()->get('coupon')['type'] ?? 0;
        $coupon = Coupon::where('code', $code)->first();
        $percent = null;
        if(isset($coupon) && $discountType == 'percent'){
            $percent = $coupon->value;
        }

        $newSubtotal =  Coupon::getCouponAmount();
        $newTax = $newSubtotal * $tax;
        $newTotal = $newSubtotal;

        $onSaleProducts = Cart::content()->filter(function ($item) {
            return $item->model->on_sale || $item->model->shipping;;
        });
    
        // Calculate shipping cost
        $freeShippingThreshold = 1000;
        $shippingCost = 0;
        $products = Product::all();

        // Check if any product is on sale or if order value meets the free shipping threshold
        if ($newTotal >= $freeShippingThreshold || $onSaleProducts->isNotEmpty() || $newTotal <= 0) {
            $shippingCost = 0;
        } else {
            $shippingCost = $setting->shipping_charge??60;
        }

        $newTax = $newSubtotal * $tax;
        $newTotal = $newSubtotal + $newTax;
        $newTotal = $newTotal + $shippingCost;
        $newSubtotal = $newSubtotal + $shippingCost;
        return collect([
            'tax' => $tax,
            'code' => $code,
            'discount' => $discount,
            'newSubtotal' => $newSubtotal,
            'newTax' => $newTax,
            'percent' => $percent,
            'newTotal' => $newTotal,
            'shippingCost' => $shippingCost,
        ]);
    }
}