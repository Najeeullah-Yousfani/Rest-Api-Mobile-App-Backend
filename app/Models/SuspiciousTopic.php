<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspiciousTopic extends Model
{
    protected $fillable =  [
        "topic_id",
        "no_of_post_per_day",
        "no_of_post_per_week",
        "no_of_post_per_month",
        "status",
        "created_at",
        "updated_at"
    ];

    protected $table = 'suspicious_topics';

    public function topic()
    {
        return $this->hasMany(Topic::class,'id','topic_id');
    }

    public function user()
    {
        return $this->hasMany(User::class,'id','user_id');
    }

    public function createRecord($data)
    {
        return $record=SuspiciousTopic::create($data);
    }
    public function refreshRecords()
    {
        return $records=SuspiciousTopic::truncate();
    }
}
