<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspiciousActivity extends Model
{
    protected $fillable =  [
        "post_id",
        "user_id",
        "poster_id",
        "activity_type",
        "difference",
        "no_of_reports",
        "status",
        "created_at",
        "updated_at"
    ];

    protected $table = 'suspicious_activities';

      /*
    * This method use for get post status.
    */
    public function getActivityTypeAttribute($value)
    {
        $status = '';
        switch ($value) {
            case 1:
                $status =  'suspicious_favourites';
                break;
            case 2:
                $status = 'suspicious_repletion';
                break;
            case 3:
                $status = 'max_reported_content';
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }

    public function post()
    {
        return $this->hasMany(Post::class,'id','post_id');
    }
    public function user()
    {
        return $this->hasMany(User::class,'id','user_id');
    }

    public function createRecord($data)
    {
        return $record=SuspiciousActivity::create($data);
    }
    public function refreshRecords()
    {
        return $records=SuspiciousActivity::truncate();
    }
}
