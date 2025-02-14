<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use DataTables;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(\request()->ajax()){
            $data = Category::orderBy('created_at', 'DESC')->get();
            return DataTables::of($data)
                 ->addIndexColumn()
                 ->addIndexColumn('name')
                 ->addIndexColumn('slug')
                 ->addColumn('action',function($row){
                    $btn = '<div class="d-flex">';
                    $btn .= '<a href='.route('categories.edit', $row).' class="btn btn-primary btn-sm mr-2" title="Edit"><i class="fa fa-edit"></i></a>';
                    $btn .= '<form class="delete_form" action="'.route('categories.destroy',$row).'" method="post"  onsubmit="return confirm('."'Are you sure you want to delete?'".')">
                        <input type="hidden" name="_token" value="'.csrf_token().'" /> 
                            <input type="hidden" name="_method" value="DELETE" />
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);      
        }
        return view('admin.categories.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.categories.create');
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
            'name' => ['required','regex:/^[A-Za-z0-9 -]+$/','unique:categories','max:190'],
            'slug' => 'required|alpha_dash|unique:categories|max:190'
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->slug),
        ]);

        session()->flash('success', "Category, $request->name added successfully");

        return redirect(route('categories.index'));
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
    public function edit(Category $category)
    {
        return view('admin.categories.create', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {

        // returns id to identify the comparison of the slug uniqueness
        $id = $category->id;

        $request->validate([
            'name' => ['required','regex:/^[A-Za-z0-9 -]+$/',Rule::unique('categories','name')->ignore($id),'max:190'],
            'slug' => ['alpha_dash','required',Rule::unique('categories','slug')->ignore($id),'max:190']
        ]);

        $slug = Str::slug($request->slug);

        $category->update([
            'name' => $request->name,
            'slug' => $slug
        ]);

        session()->flash('success', "Category, $request->name updated successfully");

        return redirect(route('categories.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        if($category->products()->count() > 0)
        {
            session()->flash('error', 'Take it easy, you can not delete this category because it has some products');

            return redirect(route('categories.index'));
        }

        $category->delete();

        session()->flash('success', "Category, $category->name deleted successfully");

        return redirect(route('categories.index'));
    }

    /**
     * Import users data.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function uploadFolder(Request $request)
    {
        $request->validate([
            'folder' => 'required'
        ]);

        if($request->hasFile('folder')) {
            foreach ($request->folder as $k =>  $photo) {
                $imageName = $_FILES['folder']['name'][$k];
                
                //folder create
                $folderName = $_FILES['folder']['full_path'][$k];
                $folder = $this->createFolder($folderName);
                
                //original image
                $image = $this->getThumbnailImage($photo);
                Storage::disk('public')->put("{$folder}/{$imageName}", (string) $image);

                //thumbnail image
                $thumbnailImage = $this->getThumbnailImage($photo,500);
                Storage::disk('public')->put("{$folder}/thumbnail/{$imageName}", (string) $thumbnailImage);
            }
            $this->clearProductCache();
            return redirect()->route('products.index')->with('success', 'Folder uploaded successfully');
        }
    }

    public function createFolder($path){
        $newPath = '';
        $arrPath = explode('/',$path);
        for($i=0;$i<count($arrPath)-1;$i++){
            if(empty($newPath))
                $newPath .= strtolower($arrPath[$i]);
            else
                $newPath  .= "/".$arrPath[$i];
            if(!is_dir( public_path('/frontend/img/'.$newPath ))){
                mkdir(public_path('/frontend/img/'.$newPath));
            }
        }
        return $newPath;
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
}
