<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class advertisementMapper extends Model
{
    use HasApiTokens, Notifiable;
    protected     $table          = 'advertisement_mapper';
    protected     $primaryKey     = 'id';
    protected     $fillable       =
    [
        'custom_add_id',
        'other_custom_add_id',
        'type',
        'created_at',
        'updated_at',
    ];

    public function getTypeAttribute($value)
    {
        $type = '';
        switch ($value) {
            case 1:
                $type =  'basic';
                break;
            case 2:
                $type = 'premium';
                break;
            default:
                $type = '';
                break;
        }
        return $type;
    }

    public function custom_add_primary()
    {
        return $this->belongsTo(CustomAdds::class, 'custom_add_id');
    }
    public function custom_add_secondary()
    {
        return $this->belongsTo(CustomAdds::class, 'other_custom_add_id');
    }
    public function getCustomAdds($like, $filter)
    {
        $records    =   advertisementMapper::
        select('id as mapper_id','custom_add_id','other_custom_add_id','type')
        ->where(function ($query) use($filter)
        {
            $query->whereHas('custom_add_primary', function ($childquery) use ($filter) {
                if ($childquery) {
                    $childquery->whereIn('status', $filter);
                }
            })
            ->orWhereHas('custom_add_secondary', function ($childquery) use ($filter) {
                if ($filter) {
                    $childquery->whereIn('status', $filter);
                }
            });

        })->with(['custom_add_primary'=>function($query)
        {

            $query->withCount(['addLike','addDislike'])
            ->with(['customAdsTopics:id,name','customAdsCountries:id,name','customAdsGender:id,custom_ads_id,gender']);

        },'custom_add_secondary'=>function($query)
        {

            $query->withCount(['addLike','addDislike'])
            ->with(['customAdsTopics:id,name','customAdsCountries:id,name','customAdsGender:id,custom_ads_id,gender']);


        }])
        ->where(function ($query) use ($like) {
            if ($like) {
                $query->where('custom_add_id', 'like', '%' . $like . '%')
                ->orWhere('other_custom_add_id', 'like', '%' . $like . '%');
            }
        });

        return $records;
    }
}
