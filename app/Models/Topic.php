<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'is_age_limit',
        "status",
        'created_at',
        'updated_at'
    ];

    protected $table = 'default_topics';
    protected $hidden = ['pivot'];

    public function topicUsers()
    {
        return $this->belongsToMany(User::class,'user_topics','def_topic_id','user_id')->withTimestamps();
    }

    public function TopicPost()
    {
        return $this->hasMany(Post::class,'user_topic_id');
    }

    public function getStatusAttribute($value)
    {
        switch ($value)
        {
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
    public function getIsAgeLimitAttribute($value)
    {
        switch ($value)
        {
            case '0':
                $status =   "false";
                break;
            case '1':
                $status =   "true";
                break;
            default:
            $status = '';
            break;
        }
        return $status;
    }
    public function checkTopicsExistence($topicIds)
    {
        $checkFlag = true;
        foreach($topicIds as $topicId)
        {
            $result = Topic::where('id',$topicId)->first();
            if(!$result)
            {
                $checkFlag = false;
                break;
            }
        }
        return $checkFlag;
    }

    public function createTopic($data)
    {
        return $topic   =   Topic::create($data);
    }

    public function getMyTopics($user)
    {
        return $user->load('userTopics');
    }

    public function updateTopic($topic, $data)
    {
        $topic->update($data);
        $topic->save();
        return $topic ? $topic : array();
    }
}
