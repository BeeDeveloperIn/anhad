<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\About;
use App\FacebookPhoto;
use App\Slide;
use App\Terms;
use App\Contact;
use App\Product;
use App\Category;
use App\Size;
use App\Facebook;
use App\SubCategory;
use App\ChildSubCategory;
use App\PrivacyPolicy;
use App\Shipping;
use App\ReturnCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\User;
use App\Notifications\WhatsappNotification;
use Illuminate\Support\Facades\Auth;
use NotificationChannels\WhatsApp\WhatsAppTextMessage;
use App\Order;
use App\SystemSetting;
class FrontendController extends Controller
{
    // Returns the platform welcome or landing page
    public function index()
    {
        $products = Product::withSum('productSize','quantity')->orderByRaw("
            CASE
                WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
                THEN created_at
                WHEN is_accessories = '1' AND products.quantity > 0 
                THEN created_at
                ELSE 0
            END
            DESC")->with('category', 'photos');

        //caching for new products
        if (Cache::has('new_products')) {
            $newProductView = Cache::get('new_products');
        }else{
            $newProducts = clone $products;
            $newProducts = $newProducts->where('is_new', 1)->paginate(10);
            $newProductView = $this->putCache('new_products','products','products',$newProducts,route('new-products'));
        }

        //caching for on sale products
        if (Cache::has('on_sale_products')) {
            $onSaleProductView = Cache::get('on_sale_products');
        }else{
            $salesProducts = clone $products;
            $salesProducts = $salesProducts->where('on_sale', 1)->paginate(10);
            $onSaleProductView = $this->putCache('on_sale_products','products','products',$salesProducts,route('sales-products'));
        }

        //caching for featured products
        if (Cache::has('featured_products')) {
            $featureProductView = Cache::get('featured_products');
        }else{
            $featuredProducts =clone $products;
            $featuredProducts = $featuredProducts->where('is_featured', 1)->paginate(10);
            $featureProductView = $this->putCache('featured_products','products','products',$featuredProducts,route('featured-products'));
        }

        //caching for latest products
        if (Cache::has('latest_products')) {
            $latestProductView = Cache::get('latest_products');
        }else{
            $latestProducts =clone $products;
            $latestProducts = $latestProducts->where('is_latest', 1)->paginate(10);
            $latestProductView = $this->putCache('latest_products','products','products',$latestProducts,route('latest-products'));
        }

        //caching for slides
        if (Cache::has('slides')) {
            $slideView = Cache::get('slides');
        }else{
            $slides = Slide::where('status',1)->orderBy('id','DESC')->get();
            $slideView = $this->putCache('slides','slide','slides',$slides);
        }

        //caching for child categories
        if (Cache::has('child_categories')) {
            $childCategoryView = Cache::get('child_categories');
        }else{
            $child_categories = ChildSubCategory::groupBy('name')->get();
            $childCategoryView = $this->putCache('child_categories','child_categories','child_categories',$child_categories);
        }

         //caching for facebook videos
        if (Cache::has('facebook_video')) {
            $facebookView = Cache::get('facebook_video');
        }else{
            $facebook = Facebook::orderBy('created_at', 'DESC')->paginate(10);
            $facebookView = $this->putCache('facebook_video','facebook.videos','facebook_video',$facebook);
        }

        return view('welcome', compact('newProductView','onSaleProductView','featureProductView','slideView','facebookView','latestProductView','childCategoryView'));
    }

    // show single product details
    public function show($id)
    {
        $product = Product::where('slug', $id)->with('photos', 'attributes')->firstOrFail();    
        $code = $this->getCodeFromString($product->code);
        $singleImage = $product->photos()->get()->first();
        $firstProducts = Product::where('id','!=',$product->id)->where('child_sub_category_id',$product->child_sub_category_id)->with('photos');
        if(!empty($code)){
            $secondProducts = Product::where('id','!=',$product->id)->where('code','LIKE',"%{$code}%")->where('child_sub_category_id',$product->child_sub_category_id)->with('photos');
            $relatedProducts = $secondProducts->union($firstProducts)->take(20)->get();
        }else{
            $relatedProducts = $firstProducts->take(20)->get();
        }
        return view('product.show', compact('product', 'relatedProducts', 'singleImage'));
    }

    // Get contact us page
    public function contact()
    {
        return view('contact');
    }

    //send message from contact us
    public function contactStore(Request $request)
    {
        // Validate contact info
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'g-recaptcha-response' => config('services.recaptcha.key') ? 'required|recaptcha' : 'nullable',
        ]);

        // Save contact info
        Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        // flash session & redirect
        return redirect()->back()->with('success', "Hey $request->name, thanks for reaching out we will get back to you withinn 24 hours");
    }

    // display all categories and products
    public function categories()
    {
        $categories = Category::with('subcategories')->get();
        //from the request
        $page = request()->has('page') ? request()->get('page') : 1;
        $perPage = 18;
        $cacheKey = 'items_pp_' . $perPage.'_p_'.$page;
        $products = Cache::remember($cacheKey, 60*24,function () use ($perPage, $page) {
            return Product::withSum('productSize','quantity')->with('photos')->orderByRaw("
            CASE
                WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
                THEN created_at
                WHEN is_accessories = '1' AND products.quantity > 0 
                THEN created_at
                ELSE 0
            END
            DESC")->paginate($perPage, ['*'], 'page', $page);
        });
        $this->setCache($cacheKey);
        return view('categories', compact('products', 'categories'));
    }

    // display a single category and its products
    public function category($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $products = $category->products()->with('photos')->withSum('productSize','quantity')->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('category', compact('slug','category', 'categories', 'products'));
    }

    // display all videos
    public function facebookVideos()
    {
        $facebook = Facebook::orderBy('created_at', 'DESC')->paginate(9);
        return view('videos', compact('facebook',));
    }

    // display a single subcategory and its products
    public function subcategory($slug)
    {
        $subCategory = SubCategory::where('slug', $slug)->firstOrFail();
        $products = $subCategory->products()->with('photos')->withSum('productSize','quantity')->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('sub-category', compact('slug','products', 'categories', 'subCategory'));
    }

    public function childsubcategory($slug)
    {
        $category = ChildSubCategory::select('name')->where('slug', $slug)->first();
        $childSubCategoryIds = ChildSubCategory::select('id')->where('name', $category->name??"")->pluck('id')->toArray();
        $products = Product::withSum('productSize','quantity')->whereIn('child_sub_category_id',$childSubCategoryIds??[])->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->with('photos')->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('categories', compact('products', 'categories'));
    }

    // display all sizes and products
    public function sizes()
    {
        $products = Product::orderBy('created_at', 'DESC')->with('photos')->paginate(9);
        $size = Size::all();
        return view('sizes', compact('products', 'size'));
    }
 
    // diplay a single size and its products
    public function size($slug)
    {
        $sizes =  Size::all();
        $products = $sizes->products()->orderBy('created_at', 'DESC')->with('photos')->paginate(9);
        return view('size', compact('sizes', 'products'));
    }

    public function facebook()
    {
        $facebook = Facebook::orderBy('created_at', 'DESC')->paginate(4);
        $facebookPhotos = FacebookPhoto::orderBy('created_at', 'DESC')->with('images')->paginate(4);
        return view('facebook', compact('facebook', 'facebook'));
    }

    // return products on sale 
    public function onSale()
    {
        $products = Product::withSum('productSize','quantity')->where('on_sale', 1)->with('photos')->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('sale', compact('categories', 'products'));
    }

    // terms and conditions
    public function terms()
    {
        $terms = Terms::first();
        return view('terms', compact('terms'));
    }

    public function shipping()
    {
        $shipping = Shipping::first();
        return view('shipping', compact('shipping'));
    }

    public function return()
    {
        $return = ReturnCancellation::first();
        return view('return', compact('return'));
    }

    // return privacy policy
    public function privacy()
    {
        $policy = PrivacyPolicy::first();
        return view('privacy', compact('policy'));
    }

    // return about us
    public function aboutUs()
    {
        $about = About::first();
        return view('about-us', compact('about'));
    }

    public function salesProduct()
    {
        $salesProducts = Product::withSum('productSize','quantity')->where('on_sale', 1)->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('sales_products', compact('categories','salesProducts'));
    }

    public function newProduct()
    {
        $newProducts = Product::withSum('productSize','quantity')->where('is_new', 1)->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('new_products', compact('categories','newProducts'));
    }

    public function featuredProducts()
    {
        $featuredProducts = Product::withSum('productSize','quantity')->where('is_featured', 1)->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('featured-products', compact('categories', 'featuredProducts'));
    }

    public function latestProducts()
    {
        $products = Product::withSum('productSize','quantity')->where('is_latest', 1)->orderByRaw("
        CASE
            WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
            THEN created_at
            WHEN is_accessories = '1' AND products.quantity > 0 
            THEN created_at
            ELSE 0
        END
        DESC")->paginate(18);
        $categories = Category::with('subcategories')->get();
        return view('latest-products', compact('categories', 'products'));
    }

    public function setCache($cacheKey){
        if(Cache::has('categories_data')){
            $arr = Cache::get('categories_data');
            if(!in_array($cacheKey,$arr))
                $arr = array_merge($arr,[$cacheKey]);
        }else{
            $arr = [$cacheKey]; 
        }
        Cache::put('categories_data',$arr,60*24);
    }

    public function testCall(){
       try{
            $user = User::first();
            $setting = SystemSetting::first();
            $data = (new WhatsappNotification($setting))->toUserOtp($user);
            \Log::info(['data' => $data]);
            // $data = WhatsAppTextMessage::create()
            // ->to('+917027336647')        
            // ->message("Hello there!\nYour invoice has been *PAID*");
            // return view('auth.otp');
        }catch(\Exception $e){
            \Log::info(['error' => $e->getMessage()]);
        }
        return 1;
    }

    public function whatsappWebhook(Request $request){
        \Log::info(['webhook' => $request->all()]);
        http_response_code(200);
    }

    public function filterData(Request $request){   

        $products = Product::withSum('productSize','quantity');

        //filter by sizes
        if($request->has('size')){
            $sizeIds =$request->size;
            $products = $products->whereHas('productSize',function($query) use($sizeIds){
                $query->whereIn('size_id',$sizeIds)->where('quantity','>',0);
            });
        }

        //filter by category, subcategory and childsubcategory
        if(!empty($request->category)){
            $category = Category::where('slug',$request->category)->first();
            $field = 'category_id';
            if(empty($category)){
                $category = SubCategory::where('slug',$request->category)->first();
                $field = 'sub_category_id';
            }
            if(empty($category)){
                $category = ChildSubCategory::where('slug',$request->category)->first();
                $field = 'child_sub_category_id';
            }
            if(!empty($category)){
                $products = $products->where($field,$category->id);
            }
        }

        //filter by child_sub_categories
        if(!empty($request->categories)){
            $childSubCategoryIds = ChildSubCategory::select('id')->whereIn('name', $request->categories)->pluck('id')->toArray();
            $products = $products->whereIn('child_sub_category_id',$childSubCategoryIds??[]);
        }
        
        //filter by on sale, is_latest, shipping, is_new and featured
        if(!empty($request->type)){
            $products = $products->where($request->type,1);
        }

        //filter by price range
        if(!empty($request->min_price) && !empty($request->max_price)){
            $products = $products->whereBetween('selling_price', [$request->min_price, $request->max_price]);
        }

        //order by prices
        if($request->sort_by == 'highest_price'){
            $products = $products->orderBy('selling_price','DESC');
        }else if($request->sort_by == 'lowest_price'){
            $products = $products->orderBy('selling_price','ASC');
        }else{
            $products = $products->orderByRaw("
            CASE
                WHEN is_accessories = '0' AND product_size_sum_quantity > 0 
                THEN created_at
                WHEN is_accessories = '1' AND products.quantity > 0 
                THEN created_at
                ELSE 0
            END
            DESC");
        }
        $products = $products->paginate(18);
        return view('ajax-products',compact('products'));
    }

    public function getCodeFromString($code){
        preg_match_all('/\d+/', $code, $matches);  
        return $matches[0][0]??'';
    }

}
