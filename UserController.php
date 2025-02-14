<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProductCSVData;
use App\SystemSetting;
use DataTables;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(\request()->ajax()){
            $data = User::where('admin','!=',1)->orderBy('id','DESC')->get();
            return DataTables::of($data)
                ->addIndexColumn('id')
                ->addIndexColumn('name')
                ->addIndexColumn('email')
                ->addColumn('avatar', function ($row) {
                    $url = "frontend/img/" . $row->image;
                    $img = '<img src=' . asset("frontend/img/avatar.jpg") . ' style="border-radius: 100%; width: 40px; height: 40px;">';
                    if ($row->checkImage()) {
                        $img = '<img src=' . asset($url) . ' style="border-radius: 100%; width: 40px; height: 40px;">';
                    }
                    return $img;
                })
                ->addColumn('address', function($row){
                   return $row->getUserAddress();
                })->addColumn('action',function($row){
                    $btn = '<div class="d-flex justify-content-between">';
                    $btn .= '<a href='.route('users.edit', $row->id).' class="btn btn-primary btn-sm mr-2" title="Edit"><i class="fa fa-edit"></i></a>';
                    $btn .= '<form class="delete_form" action="'.route('users.destroy',$row).'" method="post"  onsubmit="return confirm('."'Are you sure you want to delete?'".')">
                        <input type="hidden" name="_token" value="'.csrf_token().'" /> 
                            <input type="hidden" name="_method" value="DELETE" />
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>';
                    $btn .= '</div>';
                    return $btn;
                })
                 ->rawColumns(['action','avatar'])
                ->make(true);
                
        }
        return view('admin.users.index');
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
        //
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
        $user = User::findOrFail($id);

        return view('admin.users.edit', compact('user'));
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
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:190','regex:/^[a-zA-Z ]*$/'],
            'email' => ['nullable', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/','email', 'max:190',Rule::unique('users','email')->ignore($id)],
            'contact_number' => ['required','regex:/^\+?\d{1,10}$/',Rule::unique('users','contact_number')->ignore($user->id),'digits_between:5,15'],
            'contact_address' => 'nullable|string|max:190'
        ],['name.regex' => 'Name should contains only alphabets.','contact_number.digits_between' => 'The contact number should contain 10 digits.']);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'country_code' => $request->country_code,
            'iso_code' => $request->iso_code,
            'contact_number' => $request->contact_number,
            'contact_address' => $request->contact_address
        ]);
        session()->flash('success', "$request->name updated successfully");

        return redirect(route('users.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {

        $user->delete();

        session()->flash('success', "$user->name deleted successfully.");

        return redirect(route('users.index'));
    }

     /**
     * Import users data.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function importCsv(Request $request)
    {
        $validator = \Validator::make(
            [
                'file'      => $request->file_csv,
                'extension' => strtolower($request->file_csv->getClientOriginalExtension()),
            ],
            [
                'file'          => 'required',
                'extension'      => 'required|in:csv',
            ]
        );
        $setting = SystemSetting::first();
        if( $request->has('file_csv') ) {
            if($request->file_csv->getClientOriginalExtension() != 'csv'){
                return redirect()->back()->with('error','Please upload a csv file.');
            }
            $csv    = file($request->file_csv);
            $chunks = array_chunk($csv, 1000);
            $header = [];
            $batch  = Bus::batch([])->name('import_user_csv')->dispatch();
            foreach ($chunks as $key => $chunk) {
                $data = array_map('str_getcsv', $chunk);
                if($key == 0){
                    $header = $data[0];
                    unset($data[0]);
                }
                $batch->add(new ProductCSVData($data, $header,$setting));
            }
            return redirect()->route('users.index')
                            ->with('success', 'CSV Import added on queue. will update you once done.');
        }
    }
}