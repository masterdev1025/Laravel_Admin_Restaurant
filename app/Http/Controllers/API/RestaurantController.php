<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\Category;
use App\Restaurant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Restaurant as RestaurantResource;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'perPage' => 'integer',
            'city' => 'integer',
        ]);

        $query = Restaurant::query();

        // if restaurant_name is selected
        if ($request->has('restaurant_name')) {
            $query = $query->where('name', 'LIKE', '%'.$request->restaurant_name.'%');
        }

        // If "is_open" filter is set
        if ($request->has('is_open')) {
            $query = $query->where('is_open', '=', $request->is_open);
        }

        // If city query is selected
        if ($request->has('city')) {
            $query = $query->whereHas('categories.city', function($q) use($request){
                $q->where('cities.id', '=', $request->city);
            });
        }

        // If category query is selected
        if ($request->has('category')) {
            $query = $query->whereHas('categories', function ($q) use($request) {
                $q->where('categories.id', '=', $request->category);
            });
        }

        // Order by 'order'
        $query = $query->orderBy('order');

        if ($request->has('page')) {
            $perPage = 5;
            if ($request->has('perPage')) {
                $perPage = $request->perPage;
            }
            return RestaurantResource::collection($query->paginate($perPage));
        } else {
            return RestaurantResource::collection($query->get());
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
            'name' => 'required',
            'order' => 'integer|required'
        ]);

        // If file is set then validate file name and file type
        if ($request->filled('file')){
            $request->validate([
                'file_name' => 'required',
                'file_type'=> 'required'
            ]);
        }

        $image_url = null;
        // If file is set then validate file name and file type
        if ($request->has('file')) {

            $file = $request->file;
            if (preg_match('/^data:image\/(\w+);base64,/', $file)) {
                $data = substr($file, strpos($file, ',') + 1);
                $data = base64_decode($data);
                $file_type = $request->file_type;
                $extension = explode("/", $file_type)[1];
                $filename = $request->file_name;
                // Filename to store
                $fileNameToStore = $filename.'_'.time().'.'.$extension;
                // Upload Image
                Storage::disk('local')->put('public/restaurants/'.$fileNameToStore, $data);
                $image_url = Storage::url('public/restaurants/'.$fileNameToStore);
            }
        }

        $restaurant = Restaurant::create([
            'name' => $request->name,
            'image_url' => $image_url,
            'order' => $request->order,
            'is_open' => $request->is_open
        ]);

        if($request->has('category')) {
            $restaurant->categories()->attach($request->category);
        }

        return new RestaurantResource($restaurant);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Restaurant $restaurant)
    {
        return new RestaurantResource($restaurant);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'name' => 'required',
        ]);

        if ($request->filled('file')) {
            $image_url = null;
            $request->validate([
                'file_name' => 'required',
                'file_type' => 'required'
            ]);

            $file = $request->file;
            if (preg_match('/^data:image\/(\w+);base64,/', $file)) {
                $data = substr($file, strpos($file, ',') + 1);
                $data = base64_decode($data);
                $file_type = $request->file_type;
                $extension = explode("/", $file_type)[1];
                $filename = $request->file_name;
                // Filename to store
                $fileNameToStore = $filename.'_'.time().'.'.$extension;
                // Upload Image
                Storage::disk('local')->put('public/restaurants/'.$fileNameToStore, $data);
                $image_url = Storage::url('public/restaurants/'.$fileNameToStore);
            }
            $restaurant->update([
                'name' => $request->name,
                'image_url' => $image_url,
                'order' => $request->order,
                'is_open' => $request->is_open
            ]);
        } else {
            $restaurant->update([
                'name' => $request->name,
                'order' => $request->order,
                'is_open' => $request->is_open
            ]);
        }

        if ($request->has('category')) {
            $restaurant->categories()->sync($request->category);
        }

        return new RestaurantResource($restaurant);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Restaurant $restaurant)
    {
        $restaurant->categories()->detach();

        $restaurant->delete();
        return response()->json( null, 204);
    }

    public function insertMany(Request $request) {
        $data = $request->data;
        for($i=0;$i<count($data);$i++){
            //Restaurant::insert($data);
            if($data[$i]['action'] == 'i'){
                $restaurant = Restaurant::create([
                    'name' => $data[$i]['name'],
                    'image_url' => $data[$i]['image_url'],
                    'order' => $data[$i]['order'],
                    'is_open' => $data[$i]['is_open']
                ]);
                $restaurant->categories()->attach($data[$i]['category']);
            }
            //Restaurant::update($data);
            if($data[$i]['action'] == 'u'){
                $restaurant = Restaurant::find($data[$i]['id']);
                $restaurant->update([
                    'name' => $data[$i]['name'],
                    'image_url' => $data[$i]['image_url'],
                    'order' => $data[$i]['order'],
                    'is_open' => $data[$i]['is_open']
                ]);
                $restaurant->categories()->sync($data[$i]['category']);
            }
            //Restaurant::delete($data);
            if($data[$i]['action'] == 'd'){
                $restaurant = Restaurant::find($data[$i]['id']);
                $restaurant->categories()->detach();
                $restaurant->delete();
            }
        }
        
        return response()->json("Restaurant data updated successfully!", 200);
    }

    public function qrcode(Request $request) {
        $data = DB::table('qrcode')->where('restaurant_id', '=', $request->restaurant_id)->get();
        $response = array('data'=> $data);
        return json_encode($response);
    }

    public function qrcode_generate(Request $request) {
        $restaurant_id = $request->restaurant_id;
        $url = '54.211.162.185';
        $name = $restaurant_id.'_'.time();
        $path = 'storage/qrcode/'.$name.'.png';
        $string = $url.'_'.$name.'_'.$request->info;
        QrCode::encoding('UTF-8')->format('png')->merge('storage/qrcode/logo.jpg', 0.2, true)->errorCorrection('H')->size(1000)
               ->generate($string, public_path($path));
        $data = DB::table('qrcode')->where('restaurant_id', '=', $restaurant_id)->get();
        if(count($data) == 0){
            DB::table('qrcode')->insert([
                'restaurant_id' => $restaurant_id,
                'image_url' => $path,
                'status' => 1,
            ]);
        } else {
            DB::table('qrcode')->where('restaurant_id', '=', $restaurant_id)->update(array('image_url' => $path));
        }
        return $path;
    }

    public function qrcode_download(Request $request) {
        $data = DB::table('qrcode')->where('restaurant_id', '=', $request->restaurant_id)->get();
        $path = $data[0]->image_url;
        return response()->download($path, $request->restaurant_id.'_qrcode.png', array('content-type' => 'image/png'));
    }

    public function set_qrcode_status(Request $request) {
        DB::table('qrcode')->where('restaurant_id', '=', $request->restaurant_id)->update(array('status' => $request->status));
    }

    public function get_qrcode_status(Request $request) {
        $data = DB::table('qrcode')->where('restaurant_id', '=', $request->restaurant_id)->get();
        $status = 0;
        $result = [];
        if(count($data) > 0) {
            if($data[0]->status == 1) {
                $status = 1;
                $data = DB::table('restaurants')->where('id', '=', $request->restaurant_id)->get();
                $result = array('name'=> $data[0]->name, 'image_url'=> $data[0]->image_url);
            }
        } 
        $response = array('status'=> $status, 'data'=> $result);
        return json_encode($response);
    }
}
