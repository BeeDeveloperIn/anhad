<?php

namespace App\Http\Controllers\Admin;

use App\Imports\ProductImport;
use App\Photo;
use App\Product;
use App\Category;
use App\Size;
use App\SubCategory;
use App\ChildSubCategory;
use App\ProductAttribute;
use Excel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Product\CreateProductRequest;
use DataTables;
use Illuminate\Support\Facades\Cache;
use App\Exports\ProductExport;
class ProductController extends Controller
{
    public function __construct() {
        return $this->middleware('verifyCategoryCount')->only('create', 'update');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(\request()->ajax()){
            $data = Product::orderBy('id', 'DESC')->with('photos', 'category', 'subCategory', 'childsubCategory');
            if(!empty($request->from_date))
            {
                if($request->from_date === $request->to_date){
                    $data = $data->whereDate('created_at', $request->from_date);
                }else{
                    $to_date = date('Y-m-d',strtotime($request->to_date." +1 day"));
                    $data = $data->whereBetween('created_at', array($request->from_date, $to_date));
                }
            }
            if(!empty($request->product_type))
            {
                if($request->product_type == 1){
                    $data = $data->where('is_new', 1);
                }else if($request->product_type == 2){
                    $data = $data->where('is_featured', 1);
                }elseif($request->product_type ==3){
                    $data = $data->where('on_sale', 1);
                }else if($request->product_type == 4){
                    $data = $data->where('shipping', 1);
                }else if($request->product_type == 5){
                    $data = $data->where('is_latest', 1);
                }else if($request->product_type == 6){
                    $data = $data->where('is_accessories', 1);
                }
            }
            $data =  $data->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('category_name',function($row){
                    return $row->category?$row->category->name:'-';
                }) ->addColumn('image',function($row){
                    $img = '<img src='.asset($row->getImage()).' style="border-radius: 100%; width: 40px; height: 40px;">';
                    return $img;
                })
                ->addColumn('created_at',function($row){
                    return date('Y-m-d',strtotime($row->created_at));
                })
                 ->addIndexColumn('code')
                 ->addIndexColumn('price')
                 ->addIndexColumn('selling_price')
                 ->addColumn('action',function($row){
                    $btn = '<div class="d-flex justify-content-between">';
                    $btn .= '<a href='.route('products.edit', $row->id).' title="Edit" class="btn btn-primary btn-sm mr-2"><i class="fa fa-edit"></i></a>';
                    $btn .= '<form class="delete_form" action="'.route('products.destroy',$row->slug).'" method="post"  onsubmit="return confirm('."'Are you sure you want to delete?'".')">
                        <input type="hidden" name="_token" value="'.csrf_token().'" /> 
                            <input type="hidden" name="_method" value="DELETE" />
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>';
                    $btn .= '</div>';
                    return $btn;
                })->addColumn('size_button',function($row){
                    if($row->is_accessories == 1){
                        $btn = '-';
                    }else{
                        $btn = '<a href="'.route('products.showSizesAndQuantities', $row->id).'" 
                        class="btn btn-success btn-sm" title="Show Sizes"><i class="fa fa-eye"></i></a>';    
                    }
                    return $btn;
                })
                 ->rawColumns(['category_name','image','action','size_button'])
                ->make(true);
                   
        }
        return view('admin.products.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sizes = Size::all();
        $categories = Category::all();
        $subCategories = SubCategory::all();
        $childsubCategories = ChildSubCategory::all();
        return view('admin.products.create', compact('categories', 'subCategories', 'childsubCategories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductRequest $request)
    {
        $validator = \Validator::make($request->all(), [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
        ]);

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'code' => $request->code,
            'price' => $request->price,
            'selling_price' => $request->selling_price,
            'is_new' => $request->is_new,
            'on_sale' => $request->on_sale,
            'is_latest' => $request->is_latest,
            'is_featured' => $request->is_featured,
            'is_accessories' => $request->is_accessories??0,
            'quantity' => $request->quantity??0,
            'shipping' => $request->shipping,
            'category_id' => $request->category_id,
            'sub_category_id' => $request->sub_category_id,
            'child_sub_category_id' => $request->child_sub_category_id,
            'meta_keywords' => $request->meta_keywords,
            'meta_description' => $request->meta_description,
            'slug' => Str::slug($request->name.'-'.$request->code),
        ]);

        foreach ($request->images as $photo) {
            $name = Str::random(14);
            $extension = $photo->getClientOriginalExtension();
            $thumbnailImage = $this->getThumbnailImage($photo,500);
            $image = $this->getThumbnailImage($photo);
            $path = $name.".".$extension;

            //original image
            Storage::disk('public')->put("products/{$product->code}/{$path}", (string) $image);
            
            //thumnail image
            Storage::disk('public')->put("products/{$product->code}/thumbnail/{$path}", (string) $thumbnailImage);
            $photoRecord = Photo::create([
                'images' => $path,
                'product_id' => $product->id,
            ]);
        }

        $attributeValues = $request->attribute_value;
        $product->attributes()->createMany(
            collect($request->attribute_name)
                ->map(function ($name, $index) use ($attributeValues) {
                    return [
                        'attribute_name' => $name,
                        'attribute_value' => $attributeValues[$index],
                    ];
                })
        );
     
        $this->clearProductCache();
        session()->flash('success', "$request->name added successfully.");
        return redirect(route('products.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        $availableSizes = $product->sizes()->wherePivot('quantity','>',0)->get();
        return view('product.show', compact('product','availableSizes'));
    }

    /**
     * Show the form for editing the specified resource.products.index')
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $sizes = Size::all();
        $categories = Category::all();
        $subCategories = SubCategory::all();
        $childsubCategories = ChildSubCategory::all();
        $attributes = $product->attributes()->get();
        return view('admin.products.create', compact('product', 'categories', 'subCategories', 'attributes', 'childsubCategories'));
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
        $product = Product::findOrFail($id);
        $request->validate([
            'category_id' => 'required',
            'sub_category_id' => 'required',
            'child_sub_category_id' => 'required',
            'price' => 'required|numeric|gte:selling_price|min:1',
            'selling_price' => ['nullable','numeric','min:0'],
            'quantity' => ['required_if:is_accessories,1','min:1','nullable'],
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
        ]);
        $data = $request->only(['name', 'images.*','quantity','is_accessories', 'code', 'description','notes', 'price', 'selling_price',  'category_id', 'sub_category_id', 'child_sub_category_id', 'meta_description', 'meta_keywords', 'is_new', 'on_sale', 'is_featured', 'is_latest','shipping','created_at']);
        $product->update($data);
        if($request->hasFile('images')) {
            foreach ($request->images as $photo) {
                $name = Str::random(14);
                $extension = $photo->getClientOriginalExtension();
                $thumbnailImage = $this->getThumbnailImage($photo,500);
                $image = $this->getThumbnailImage($photo);
                $path = $name.".".$extension;

                //original image
                Storage::disk('public')->put("products/{$product->code}/{$path}", (string) $image);
                
                //thumnail image
                Storage::disk('public')->put("products/{$product->code}/thumbnail/{$path}", (string) $thumbnailImage);

                $photoRecord = Photo::create([
                    'images' => $path,
                    'product_id' => $product->id,
                ]);
            }
        }
        $attributeValues = $request->attribute_value;
        $product->attributes()->createMany(
            collect($request->attribute_name)
                ->map(function ($name, $index) use ($attributeValues) {
                    return [
                        'attribute_name' => $name,
                        'attribute_value' => $attributeValues[$index],
                    ];
                })
        );

        $this->clearProductCache();
        session()->flash('success', "$product->name updated successfully.");
        return redirect(route('products.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $allImages = $product->photos;
        foreach ($allImages as $key => $img) {
            // Storage::disk('public')->delete($product->getUrl().'/'.$img->images);
            if(file_exists($product->getUrl().'/'.$img->images)){
                unlink($product->getUrl().'/'.$img->images);
            }
            if(file_exists($product->getUrl().'/thumbnail/'.$img->images)){
                unlink($product->getUrl().'/thumbnail/'.$img->images);
            }
        }
        $product->photos()->delete();
        $product->attributes()->delete();
        $this->clearProductCache();
        $product->delete();
        session()->flash('success', "$product->name deleted successfully.");
        return redirect(route('products.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyImage($id)
    {
        $image = Photo::find($id);
        if(file_exists($image->product->getUrl().'/'.$image->images)){
            unlink($image->product->getUrl().'/'.$image->images);
        }
        if(file_exists($image->product->getUrl().'/thumbnail/'.$image->images)){
            unlink($image->product->getUrl().'/thumbnail/'.$image->images);
        }
        $this->clearProductCache();
        $image->delete();
        session()->flash('success', "Image deleted successfully.");
        return redirect()->back();
    }

    /**
     * Remove the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyAttribute($id)
    {
        $attribute = ProductAttribute::find($id);
        $attribute->delete();
        session()->flash('success', "Attribute deleted successfully.");
        return redirect()->back();
    }

    public function showSizesAndQuantities($id)
    {
        $product = Product::findOrFail($id);
        $sizes = Size::orderBy('position','ASC')->get();
        return view('admin.products.showSizesAndQuantities', compact('product', 'sizes'));
    }

    public function updateSizesAndQuantities(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        foreach ($request->sizes as $sizeId => $quantity) {
            $product->sizes()->syncWithoutDetaching([$sizeId => ['quantity' => $quantity,'is_active' => $request->is_active[$sizeId]]]);
        }
        return redirect()->back()->with('success', 'Sizes and quantities updated successfully.');
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv',
        ]);
        try{
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                Excel::import(new ProductImport, $request->file);
                $this->clearProductCache();
                return redirect()->back()->with('success', 'File uploaded successfully.');
            }
        }catch(\Exception $e){
            return redirect()->back()->with('error',isset($e->errorInfo[2])?$e->errorInfo[2]:$e->getMessage());
        }
    }

    public function salesProducts()
    {
        $salesProducts = Product::where('on_sale', 1)->paginate(10);
        return view('sales_products', compact('salesProducts'));
    }

    /**
     * Update the order of product images.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateImageOrder(Request $request)
    {
        $imageId = $request->input('imageId');
        $newIndex = $request->input('newIndex');
        $photo = ProductPhoto::find($imageId);
        $photo->order_column = $newIndex;
        $photo->save();
        return response()->json(['success' => true]);
    }

    public function fetchSubcategories(Request $request)
    {
        $data['subCategories'] = SubCategory::where("category_id", $request->category_id)
                                ->get(["name", "id"]);
        return response()->json($data);
    }
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function fetchChildSubcategories(Request $request)
    {
        $data['childSubCategories'] = ChildSubCategory::where("sub_category_id", $request->sub_category_id)
                                    ->get(["name", "id"]);
        return response()->json($data);
    }

    public function getThumbnailImage($photo,$size = 3000){
        $extension = $photo->getClientOriginalExtension();
        $image = Image::make($photo);
        $image->orientate();
        $image->resize($size, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        return $image->encode($extension);
    }

    public function productExport(Request $request)
    {
        $extension = $request->type?'csv':'xlsx';
        return Excel::download(new ProductExport($request), 'products.'.$extension);
    }

}
