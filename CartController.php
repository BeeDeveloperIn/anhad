<?php

namespace App\Http\Controllers;

use App\Product;
use App\Coupon;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $mightAlsoLike = Product::inRandomOrder()->with('photos')->take(8)->get();
        $discount = 0;
        $coupon = session()->get('coupon');
        $discountType = session()->get('coupon')['type'] ?? 0;
        $newSubtotal = isset($coupon['discount'])?Coupon::getCouponAmount():(Cart::subtotal(2,'.','') - $discount);
        $newTotal = $newSubtotal;
        return view('cart', compact('mightAlsoLike'))->with([
            'discount' => Coupon::getCouponDiscount(),
            'newSubtotal' => $newSubtotal,
            'newTotal' => $newTotal,
            'discountType' => $discountType,
            'icon' => "<i class='fas fa-rupee-sign'></i>"
        ]);
    }

    public function applyFreebie(){
        $discount = 0;
        $couponDiscount = session()->get('coupon');
        $discountType = session()->get('coupon')['type'] ?? 0;
        $newSubtotal = $this->getCartTotal();
        $coupon = Coupon::where('type','freebie')->where('is_active',1)->where('min_amount','<=',$newSubtotal)->orderBy('min_amount', 'desc')->first();
        if(!empty($coupon) &&  !in_array($discountType,['percent','fixed'])){
            session()->put('coupon', [
                'name' => $coupon->code,
                'discount' => $coupon->value,
                'totalAmount' => $coupon->discount($this->getCartTotal()),
                'type' => $coupon->type,
                'description' => $coupon->description
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rowId = Cart::search(function ($cartItem, $rowId) use ($request) {
            return $cartItem->id  === $request->id && $cartItem->options->size_id === $request->size_id;
        })->first()->rowId??'';
        if (!empty($rowId)) {
            $cartData = Cart::content()->where('rowId',$rowId)->first();
            Cart::update($rowId, $cartData->qty + $request->quantity);
            session()->flash('success', "$request->name quantity update to your cart successfully!");
            return redirect(route('cart.index'));
        }

        Cart::add($request->id, $request->name, $request->quantity, $request->price, ['max_quantity'=>$request->max_quantity??1,'size_id' => $request->size_id,'size' => $request->size_name, 'color' => $request->Color])->associate('App\Product');

        // apply freebie
        $this->applyFreebie();

        session()->flash('success', "$request->name added to your cart successfully!");

        return redirect(route('cart.index'));
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
        $quantity = $request->quantity;
        // Update cart quantity
        Cart::update($id, $request->quantity);
        $this->removeCouponCode();
        // If it's an AJAX request, return JSON response with updated quantity
        if ($request->ajax()) {
            return response()->json(['quantity' => $request->quantity]);
        }

        // apply freebie
        $this->applyFreebie();
        
        // Otherwise, redirect back with a success message
        session()->flash('success', "$request->name updated successfully!");
        return redirect(route('cart.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cartData = Cart::content()->where('rowId',$id)->first();
        if($cartData){
            Cart::remove($id);
            $this->removeCouponCode();
            session()->flash('success', "Item removed successfully!");
        }else{
            session()->flash('success', "Item is already deleted!");
        }
        return redirect()->back();
    }

    public function removeCouponCode(){
        $coupon = session()->get('coupon');
        if(isset($coupon)){
            $couponExist = Coupon::where('code', $coupon['name'])
            ->where('is_active',1)
            ->where('start_date','<=',date('Y-m-d'))
            ->where('end_date','>=',date('Y-m-d'))->first();
            if(!$couponExist) {
                session()->forget('coupon');
            }
        
            if(isset($couponExist) && $couponExist->first_use_valid && $this->getCartTotal() < $couponExist->min_amount){
                session()->forget('coupon');
            }elseif(isset($couponExist) && $this->getCartTotal() < $couponExist->min_amount){
                session()->forget('coupon');
            }elseif(isset($coupon) && $coupon['type'] == 'fixed' && $coupon['discount'] > $this->getCartTotal()){
                session()->forget('coupon');
            }
        }
        if(Cart::content()->isEmpty()){
            session()->forget('coupon');
        }
    }

}
