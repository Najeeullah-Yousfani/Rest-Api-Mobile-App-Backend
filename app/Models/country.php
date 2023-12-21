<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class country extends Model
{
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'country_code',
        'name',
        'status'
    ];

     /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'updated_at',
        'pivot'

    ];

    protected $table = 'countries';

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function countryPost()
    {
        return $this->hasMany(Post::class);
    }

    public function countryCustomAds()
    {
        return $this->belongsToMany(CustomAdds::class,'custom_ads_countries','country_id','custom_ads_id')->withTimestamps();
    }

    public function getStatusAttribute($value)
    {
        switch ($value)
        {
            case '1':
                $status =   'active';
                break;
            case '2':
                $status =   'in-active';
                break;
            default:
            $status = '';
            break;
        }
        return $status;
    }

    public function getLocations($offset, $limit, $sort, $like)
    {
        $data   =   country::where(function ($query) use ($like) {
            if ($like) {
                $query->where('name', 'like', '%' . $like . '%');
            }
        })
        ->orderBy('id', $sort);

        $count      =   $data->count();
        $data       =   $data->take($limit)->skip($offset)->get();
        $response   =   ['data'=>$data, 'count'=>$count];
        return $response;
    }

    public function updateCountry($id, $data)
    {
        $country =  Country::find($id);
        $country->update($data);
        $country->timestamps = false;
        $country->save();
        return $country ? $country : array();
    }

    public function checkIfCountriesExist($countries)
    {
        $checkFlag = true;
        foreach($countries as $countryId)
        {
            $result = Country::where('id',$countryId)->first();
            if(!$result)
            {
                $checkFlag = false;
                break;
            }
        }
        return $checkFlag;
    }

}
