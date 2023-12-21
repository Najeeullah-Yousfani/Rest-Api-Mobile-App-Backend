<?php

namespace App\Models;

use App\config\Constants;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class PostRanking extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'current_rank',
        'highest_rank',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    protected $table = 'post_rankings';

    public function rankingPost()
    {
        return $this->belongsTo(Post::class,'post_id');
    }


    public function updatePostRanking($id, $data)
    {
        $posts =  PostRanking::where('post_id',$id)->first();
        if ($posts) {
            $posts->update($data);
            $posts->save();
            return $posts ? $posts : array();
        }
        $data['post_id']    =   $id;
        $posts =  PostRanking::create($data);
        return $posts ? $posts : array();
    }

    public function getTopUsers($user,$topic, $startDate, $endDate)
    {
        return $postUser = PostRanking::select('users.id','users.country_id','users.username','users.profile_image','users.thumb_image',
        DB::raw('(Select min(post_rankings.highest_rank) from post_rankings where post_rankings.post_id=posts.id) as highest_rank'),
        DB::raw('(Select max(post_rankings.current_rank) from post_rankings where post_rankings.post_id=posts.id) as current_rank')
        ,DB::raw('COUNT(DISTINCT(post_rankings.post_id)) as top_rank_post_count'))
        ->leftJoin('posts','post_rankings.post_id','posts.id')
        ->leftJoin('users','posts.user_id','users.id')
        ->leftJoin('countries','countries.id','users.country_id')
        ->where('countries.status',Constants::COUNTRY_TYPE_ACTIVE)
        ->where('users.status',Constants::USER_STATUS_ACTIVE_INT)
        ->where(function ($query) use ($topic) {
            if ($topic) {
                $query->whereIn('posts.user_topic_id', $topic);
            }
        })
        ->where(function ($query) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $query->whereBetween('posts.created_at', [$startDate, $endDate]);
            }
        })
        ->groupBy('users.id')
        ->orderBy('top_rank_post_count','desc');
    }
}
