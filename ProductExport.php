<?php

namespace App\Exports;

use App\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Size;
class ProductExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $data;
    protected $from_date;
    protected $to_date;
    protected $product_type;
    protected $search;
    public function __construct($request)
    {
        $this->search = $request->search;
        $this->from_date = $request->from_date;
        $this->to_date = $request->to_date;
        $this->product_type = $request->product_type;
        $this->sizes = Size::select('id','name')->get();
    }

    public function collection()
    {
        $search = $this->search;
        $product =  Product::orderby('id','ASC');
        if(!empty($this->search)){
            $product->where(function ($query) use($search){
                    $query->where('name', 'like', "%" . $search . "%")
                    ->orWhere('code', 'like', "%" . $search . "%")
                    ->orWhere('price', 'like', "%" . $search . "%")
                    ->orWhere(function ($query) use($search){
                        $query->whereHas('category', function ($q) use ($search) {
                            $q->where('name', 'like', "%" . $search . "%");
                        });
                    })
                    ->orWhere(function ($query) use($search){
                        $query->whereHas('subCategory', function ($q) use ($search) {
                            $q->where('name', 'like', "%" . $search . "%");
                        });
                    })
                    ->orWhere(function ($query) use($search){
                        $query->whereHas('childsubCategory', function ($q) use ($search) {
                            $q->where('name', 'like', "%" . $search . "%");
                        });
                    })
                    ->orWhere('selling_price', 'like', "%" . $search . "%");
            });
        }
        if(!empty($this->from_date))
        {
            if($this->from_date === $this->to_date){
                $product = $product->whereDate('created_at', $this->from_date);
            }else{
                $product = $product->whereBetween('created_at', array($this->from_date, $this->to_date));
            }
        }
        if(!empty($this->product_type))
        {
            if($this->product_type == 1){
                $product = $product->where('is_new', 1);
            }else if($this->product_type == 2){
                $product = $product->where('is_featured', 1);
            }elseif($this->product_type ==3){
                $product = $product->where('on_sale', 1);
            }else if($this->product_type == 4){
                $product = $product->where('shipping', 1);
            }else if($this->product_type == 5){
                $product = $product->where('is_latest', 1);
            }else if($this->product_type == 6){
                $product = $product->where('is_accessories', 1);
            }
        }
        return $product->get();
    }

    public function headings():array{
        $sizedata = [];
        if(!empty($this->sizes)){
            foreach($this->sizes as $size){
                $sizedata[] = $size->name;
            }
        }
        $data = [
            'name',
            'code',
            'description',
            'price',
            'Selling price',
            'category_id',
            'sub_category_id',
            'child_sub_category_id',
            'on_sale',
            'is_new',
            'is_featured',
            'shipping',
            'is_latest',
            'is_accessories',
            'quantity',
            'Image',
            'created_at'
        ];
        $data = array_merge($data,$sizedata);
        return $data;
    } 

    public function map($product): array
    {
        $sizedata = [];
        if(!empty($this->sizes)){
            foreach($this->sizes as $size){
                $quantity = $product->productSize()->select('quantity')->where('size_id',$size->id)->first();
                $sizedata[] = !empty($quantity) && !empty($quantity->quantity)?$quantity->quantity:'0';
            }
        }
        $data = [
            $product->name,
            $product->code,
            $product->description,
            $product->price,
            $product->selling_price??'0.00',
            $product->category->name??'-',
            $product->subcategory->name??'-',
            $product->childsubCategory->name??'-',
            (string)$product->on_sale,
            (string)$product->is_new,
            (string)$product->is_featured,
            (string)$product->shipping,
            (string)$product->is_latest,
            (string)$product->is_accessories,
            $product->quantity,
            null,
            $product->created_at
        ];
        $data = array_merge($data,$sizedata);
        return $data;
    }
}
