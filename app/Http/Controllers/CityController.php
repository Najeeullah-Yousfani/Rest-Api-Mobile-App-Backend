<?php

namespace App\Http\Controllers;

use App\Models\city;
use App\Models\country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * Cities : Get aLl active cities
     */
    public function getAllCities(Request $request,$id)
    {
        $offset = $request->input('offset') ? $request->input('offset') : 0;
        $limit  =   $request->input('limit') ? $request->input('offset') : 146296;
        $like   =   $request->input('like');
        $country = country::find($id);
        if(!$country)
        {
            return response()->json(['status'=>400,'success'=>false,'error'=>'Please enter a valid country id']);
        }
        $cityModel = new city();
        $data = $cityModel->getActiveCitiesByCountryId($id,$like);
        $count = $data;
        $count = $count->count();
        $data = $data->take($limit)->skip($offset);
        $data =  $data ? $data : [];
        $data = $data->get()->toArray();
        return response()->json(
            [
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'data fetched successfully',
                'count'     =>  $count,
                'data'      =>  $data
            ]
        );
    }
}
