<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Models\country;
use Illuminate\Http\Request;
use CountryFlag;
use Illuminate\Support\Arr;

class CountryController extends Controller
{
    /**
     * Get All Countries
     */
    public function getallCountries(Request $request)
    {
        $offset = $request->input('offset') ? $request->input('offset') : 0;
        $limit  =   $request->input('limit') ? $request->input('limit') : 250;
        $data = country::where('status', Constants::STATUS_ACTIVE)->get();
        $count  =  count($data);
        $data = $data->take($limit)->skip($offset);
        $data =  $data ? $data : [];
        $data = $data->toArray();
        $dataWithFlag = [];
        $index = 0;
        if ($count > 0) {
            foreach ($data as $key => $country) {
                if ($country['country_code'] == "GB") {
                    $index = $key;
                }
                $flag['flag']   =   CountryFlag::get($country['country_code']);
                array_push($dataWithFlag, array_merge($country, $flag));
            }

            $item = array_slice($dataWithFlag, $index, 1);
            unset($dataWithFlag[$index]);
            array_unshift($dataWithFlag, $item[0]);
        }else
        {
            $dataWithFlag   =   [];
        }

        return response()->json(
            [
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'data fetched successfully',
                'count'     =>  $count,
                'data'      =>  $dataWithFlag
            ]
        );
    }

    /**
     *
     *   A list of all countries will be available
     *   Admin can search the location by the country name
     *   Admin can enable/disable any location by tapping on the icon against the name of the country, posts created under that location will not be shown to the users if it has been disabled by the admin.
     *   User from the disabled location can use the app from that location that has been disabled by the admin if they're already registered, but they won't be able to view post that has been posted by the user of that disabled location. Posts created from their profile will also be hidden from the app but user can view their post in their profile view
     *   New user will not be able to view the disabled country from the dropdown while onboarding
     *   User can change their location from the account setting menu
     */
    public function getLocation(Request $request)
    {
        $offset =   $request->input('offset') ? $request->input('offset') : 0;
        $limit  =   $request->input('limit') ? $request->input('limit') : 10;
        $sort   =   $request->input('sort')   ? $request->input('sort')   : 'asc';
        $like   =   $request->input('like');

        $countryModel   =   new Country();
        $data   =   $countryModel->getLocations($offset, $limit, $sort, $like);
        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data fetched Successfuly',
            'count'     =>  $data['count'],
            'data'      =>  $data['data']
        ]);
    }

    public function toggleLocation(Request $request)
    {
        $targetLocId   =   $request->input('loc_id');

        $countryModel           =   new Country();
        $countryById            =   Country::find($targetLocId);
        if (!$countryById) {
            return response()->json(['status' => '400', 'error' => 'Please enter a valid id'], 400);
        }
        if ($countryById->status == 'active') {
            $changed_status = 2;
        } else {
            $changed_status = 1;
        }
        $data['status']         =   $changed_status;
        $data['timestamps']     =   false;
        $updateLoc              =   $countryModel->updateCountry($targetLocId, $data);
        if ($updateLoc) {
            return response()->json([
                'status'    =>  200,
                'success'   =>  'true',
                'message'   =>  'Post updated successfuly'
            ]);
        }

        return response()->json([
            'status'    =>  400,
            'success'   =>  'false',
            'error'     =>  'Post update failed'
        ]);
    }
}
