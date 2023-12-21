<?php

namespace App\Models;

use App\config\Constants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class city extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'country_id',
        'name',
        'status'
    ];

    protected $table = 'cities';

    public function getStatusAttribute($value)
    {
        switch ($value) {
            case '1':
                $status =   'Active';
                break;
            case '2':
                $status =   'In-Active';
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }

    public function getActiveCitiesByCountryId($id,$like)
    {
        return $data = city::where(['status' => Constants::STATUS_ACTIVE, 'country_id' => $id])
            ->where(function ($query) use ($like) {
                if ($like) {
                    $query->where('cities.name','like', '%' . $like . '%');
                }
            });
    }
}
