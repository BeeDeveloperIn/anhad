<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\FacebookController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ChildSubCategory;
use Illuminate\Support\Facades\Route;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Http\Controllers\TickerController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\Auth\LoginController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Open Routes
Route::get('/', 'FrontendController@index')->name('welcome');
Route::get('on-sale', 'FrontendController@onSale')->name('on-sale');
Route::get('/category/{slug}', 'FrontendController@category')->name('frontendCategory');
Route::get('/categories', 'FrontendController@categories')->name('frontendCategories');
Route::get('/sub-category/{slug}', 'FrontendController@subcategory')->name('subcategory');
Route::get('/child-sub-category/{slug}', 'FrontendController@childsubcategory')->name('childsubcategory');
Route::get('/product/{slug}', 'FrontendController@show')->name('single-product');
Route::post('/contact', 'FrontendController@contactStore')->name('store-contact');
Route::get('/about', 'FrontendController@aboutUs')->name('about-us');
Route::get('/contact', 'FrontendController@contact')->name('contact-us');
Route::get('/terms-and-conditions', 'FrontendController@terms')->name('terms.conditions');
Route::get('/shipping', 'FrontendController@shipping')->name('shipping');
Route::get('/return', 'FrontendController@return')->name('return');
Route::get('/privacy-policy', 'FrontendController@privacy')->name('privacy-policy');
Route::get('/size/{slug}', 'FrontendController@size')->name('frontendSize');
Route::get('/sizes', 'FrontendController@sizes')->name('frontendSizes');
Route::any('/search-result', 'FrontendController@filterData')->name('search.result');

Route::resource('cart', 'CartController');
Route::resource('wishlist', 'WishlistController');
Route::post('coupons', 'CouponsController@store')->name('coupons.store');
Route::delete('coupons', 'CouponsController@destroy')->name('coupons.destroy');
Route::get('/checkout', 'PaymentController@checkout')->name('checkout');
Route::post('/payment', 'PaymentController@makePayment')->name('payment');
Route::post('/payment/success', 'PaymentController@paymentsuccess')->name('payment.success');

//Paypal Routes
Route::get('paypal-checkout/{order}', 'PaypalController@paypalCheckout')->name('paypal.checkout');
Route::get('paypal-success', 'PaypalController@paypalSuccess')->name('paypal.success');
Route::post('payment-success', 'RazorpayController@paymentSuccess')->name('payment.success');
Route::get('paypal-cancel', 'PaypalController@paypalCancel')->name('paypal.cancel');
Route::get('/sales-products', 'FrontendController@salesProduct')->name('sales-products');
Route::get('/new-products', 'FrontendController@newProduct')->name('new-products');
Route::get('/featured-products', 'FrontendController@featuredProducts')->name('featured-products');
Route::get('/todays-live', 'FrontendController@latestProducts')->name('latest-products');
Route::get('/whatsapp', 'FrontendController@testCall')->name('whatsapp');
Route::get('/facebook-videos', 'FrontendController@facebookVideos')->name('facebook-videos');
Route::post('webhook', 'FrontendController@whatsappWebhook')->name('whatsapp.webhook');

// Authenticated users routes
Route::middleware('auth')->group(function () {
	Route::get('my-orders', 'ProfileController@index')->name('my-orders.index');
	Route::get('my-profile', 'ProfileController@edit')->name('my-profile.edit');
	Route::post('my-profile', 'ProfileController@update')->name('my-profile.store');
	Route::post('add-rating', 'ProfileController@addRating')->name('add.rating');
	Route::post('get-rating-form', 'ProfileController@getRatingForm')->name('rating.modal.form');
	Route::get('my-orders/{id}', 'ProfileController@show')->name('my-profile.show');
	Route::get('change-password', 'ProfileController@changePassword')->name('change-password');
	Route::post('change-password', 'ProfileController@updatePassword')->name('update-password');
	Route::resource('orders', 'OrderController');
	Route::resource('checkout', 'CheckoutController');
	Route::get('orders/{id}/invoice', [OrderController::class,'generateInvoicePdf'])->name('orders.invoice');
	Route::post('checkout-order', 'CheckoutController@store')->name('checkout-order.store');
	Route::post('proceed-to-pay', 'CheckoutController@razorpaycheck')->name('proceed-to-pay.razorpaycheck');
});

// Login & Register Routes
Auth::routes();
Route::get('login/{provider}', 'Auth\LoginController@redirectToProvider');
Route::get('login/{provider}/callback', 'Auth\LoginController@handleProviderCallback');
Route::post('/contact/store', [ContactController::class, 'store'])->name('store-contact');
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
Route::post('send-otp', 'Auth\LoginController@sendOtp')->name('send.otp');
Route::post('resend-otp', 'Auth\LoginController@resendOtp')->name('resend.otp');
Route::get('/verify-otp', 'Auth\LoginController@userOtp')->name('user.send.otp');
Route::post('admin-login', 'Auth\LoginController@adminLogin')->name('admin.login');
Route::get('admin/login', 'Auth\LoginController@adminLoginPage')->name('admin.login.page');


// Adminstrator Routes
Route::middleware(['auth', 'admin'])->group(function () {
	Route::any('order-pdf', 'Admin\OrderController@generateOrderPdf')->name('order.pdf');
	Route::any('/products/export-product',  [ProductController::class,'productExport'])->name('product.export');
	Route::any('/admin/orders/export-order',  'Admin\OrderController@orderExport')->name('order.export');
	Route::get('/home', 'HomeController@index')->name('home');
	Route::resource('admin/users', 'Admin\UserController');
	Route::resource('admin/slides', 'Admin\SlideController');
	Route::resource('admin/categories', 'Admin\CategoryController');
	Route::post('rating/change-status', 'Admin\RatingController@changeStatus')->name('change.rating.status');
	Route::post('size/change-status', 'Admin\SizeController@changeStatus')->name('change.size.status');
	Route::post('upload/folder', 'Admin\CategoryController@uploadFolder')->name('upload.folder');
	Route::post('coupon/change-status', 'Admin\CouponController@changeStatus')->name('change-status');
	Route::post('slide/change-status', 'Admin\SlideController@changeStatus')->name('change.slide.status');
	Route::post('coupon/change-validity', 'Admin\CouponController@changevalidity')->name('change-validity');
	Route::resource('admin/subcategories', 'Admin\SubCategoryController');
	Route::resource('admin/childsubcategories', 'Admin\ChildSubCategoryController');
	Route::delete('admin/products/photo/{id}', 'Admin\ProductController@destroyImage')->name('destroyImage');
	Route::delete('admin/products/attribute/{id}', 'Admin\ProductController@destroyAttribute')->name('destroyAttribute');
	Route::resource('admin/coupon', 'Admin\CouponController');
	Route::resource('admin/products', 'Admin\ProductController');
	Route::resource('admin/system-settings', 'Admin\SystemSettingsController');
	Route::get('/admin/contact', 'Admin\MessageController@index')->name('contactMessages');
	Route::get('/admin/contact/{id}', 'Admin\MessageController@show')->name('contact.show');
	Route::get('/admin/orders', 'Admin\OrderController@index')->name('orders.index');
	Route::get('/admin/pending-orders', 'Admin\OrderController@pendingOrders')->name('pending.orders');
	Route::get('/admin/orders/{id}', 'Admin\OrderController@show')->name('orders.show');
	Route::patch('/admin/orders/{id}', 'Admin\OrderController@update')->name('orders.update');
	Route::resource('admin/about', 'Admin\AboutController');
	Route::resource('admin/terms', 'Admin\TermsController');
	Route::resource('admin/shipping', 'Admin\ShippingController');
	Route::resource('admin/return', 'Admin\ReturnCancellationController');
	Route::resource('admin/privacy', 'Admin\PrivacyPolicyController');
	Route::resource('admin/social-links', 'Admin\SocialLinkController');
	Route::resource('admin/shipping-options', 'Admin\ShippingOptionController');
	Route::resource('admin/sizes', 'Admin\SizeController');
	Route::resource('admin/ratings', 'Admin\RatingController');
	Route::post('/order/status-update-all', [OrderController::class, 'statusUpdateAll'])->name('orders.update.status.all');
	Route::post('/order/excel', [OrderController::class, 'uploadExcel'])->name('order.excel');
	Route::get('/products/{id}', [ProductController::class,'show'])->name('products.show');
	Route::post('/upload/excel', [ProductController::class, 'uploadExcel'])->name('upload.excel');
	Route::post('/upload/csv', [UserController::class, 'importCsv'])->name('upload.user.csv');
	Route::put('/products/{id}/sizes-and-quantities', [ProductController::class, 'updateSizesAndQuantities'])->name('products.updateSizesAndQuantities');
	Route::get('/products/{id}/sizes-and-quantities', [ProductController::class, 'showSizesAndQuantities'])->name('products.showSizesAndQuantities');
	Route::resource('admin/facebook', 'Admin\FacebookController');
	Route::get('admin/facebook/videos', 'Admin\FacebookController@fetchVideos');
	Route::get('admin/top-selling-product', 'Admin\TopSellingProductController@index');
	Route::get('/products/{id}/edit', 'Admin\ProductController@edit')->name('products.edit');
	Route::any('/products/subcategories', [ProductController::class,'fetchSubcategories'])->name('fetch-subcategories');
	Route::any('/products/child-subcategories', [ProductController::class,'fetchChildSubcategories'])->name('fetch-child-subcategories');
	Route::post('admin/sizes/reorder', 'Admin\SizeController@reorder')->name('sizes.reorder');
	// Route::get('/ticker', 'TickerController@index');
	Route::get('/tickers', [TickerController::class, 'index'])->name('tickers.index');
	Route::get('/tickers/create', [TickerController::class, 'create'])->name('tickers.create');
	Route::post('/tickers', [TickerController::class, 'store'])->name('tickers.store');
	Route::get('/tickers/{id}', [TickerController::class, 'show'])->name('tickers.show');
	Route::get('/tickers/{id}/edit', [TickerController::class, 'edit'])->name('tickers.edit');
	Route::put('/tickers/{id}', [TickerController::class, 'update'])->name('tickers.update');
	Route::delete('/tickers/{id}', [TickerController::class, 'destroy'])->name('tickers.destroy');
	Route::post('/webhook', [WebhookController::class, 'handleIncomingMessage']);
	// Route::get('/sales-products', 'ProductController@salesProducts')->name('sales-products');
	// Route::get('/sales-products', [ProductController::class, 'salesProducts'])->name('sales-products');
	Route::get('/product/{id}', [ProductController::class, 'show'])->name('product.show');
	Route::get('/facebook/videos', [FacebookController::class, 'fetchVideos']);

	// Route::get('login/facebook', [LoginController::class, 'redirectToFacebook']);
	Route::get('callback/facebook', [LoginController::class, 'handleFacebookCallback']);

	Route::get('/instagram/videos', [InstagramController::class, 'fetchInstagramVideos']);
	Route::get('/videos', [FacebookController::class, 'showVideos'])->name('videos');

	// Route::get('/login/facebook', [FacebookController::class, 'loginfacebook'])->name('login');
	Route::get('/callback', [FacebookController::class, 'callback'])->name('callback');
	// Route::get('/about', [AboutController::class, 'index'])->name('about.index');

	// Define the route for updating image order
	Route::post('/update-image-order', [ProductController::class, 'updateImageOrder'])->name('updateImageOrder');
});
