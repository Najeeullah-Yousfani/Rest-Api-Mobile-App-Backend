<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\config\Constants;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use DB;
use Illuminate\Support\Arr;

class PostReaction extends Model
{
    const POST_LIKE_STATUS          = 1;

    use HasApiTokens, Notifiable;
    protected     $table          = 'post_reactions';
    protected     $primaryKey     = 'id';
    public        $timestamps     = false;
    protected     $fillable       =
    [
        'id',
        'user_id',
        'post_like_id',
        'post_dislike_id',
        'status',
        'created_at',
    ];
    /*
    * This method is used for add post reaction.
    */
    public function addPostReaction($data)
    {
        return $add = PostReaction::create($data);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactionPost()
    {
        return $this->belongsTo(User::class);
    }

    public function postLiked()
    {
        return $this->belongsTo(Post::class, 'post_like_id');
    }

    public function postDisliked()
    {
        return $this->belongsTo(Post::class, 'post_dislike_id');
    }


    /*
    * This method is used for check like exist or not.
    */
    public function checkLikeExists($userId, $postId)
    {
        return $check = PostReaction::where(['user_id' => $userId, 'post_id' => $postId])->first();
    }
    /*
    * This method is used for count post likes.
    */
    public function countPostLikes($userId, $postLikeId)
    {
        return $check = PostReaction::where(['user_id' => $userId, 'post_like_id' => $postLikeId, 'status' => self::POST_LIKE_STATUS])->count();
    }
    /*
    * This method is used for get post reaction by id.
    */
    public function findPostReactById($post_like_id)
    {
        return $get = PostReaction::select('post_dislike_id')
            ->where('post_like_id', $post_like_id)
            ->orderBy('id', 'DESC')->first();
    }
    /*
    * This method is use for get user monthly visits.
    */
    public function getUserVisitsMonthly($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $post      = new Post();
        $getAge    = $post->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];
        $startDate  = Carbon::parse($start_date);
        $endDate    = Carbon::parse($end_date);
        $diff       =   $startDate->diffInMonths($endDate);
        $startDate  = $startDate->format('Y m');
        $endDate    = $endDate->format('Y m');
        $pivot     = 'months';
        $mapDate   =  "%Y %m";
        $dateFormate    =   "%Y-%m";
        $countryRaw     =   implode(',', $country);

        if ($diff == 0) {
            $startDate  = Carbon::parse($start_date)->format('Y-m-d');
            $endDate    = Carbon::parse($end_date)->format('Y-m-d');
            $pivot     = 'days';
            $mapDate   =  "%Y-%m-%d";
            $dateFormate    =   "%Y-%m-%d";
        }


        $genderWhere = ($gender) ? 'gender = "' . $gender . '" AND ' : '';
        $countryWhere = ($country) ? 'users.country_id IN(' . $countryRaw . ') AND' : '';
        $ageWhere = ($start_age) ? '(DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), \'' . '%Y' . '\')+0) BETWEEN ' . $start_age . ' AND ' . $end_age . ' AND' : '';

        // dd($startDate);
        $getUsers =  PostReaction::select(
            DB::raw('DATE_FORMAT(post_reactions.created_at, "' . $dateFormate . '") as ' . $pivot),
            'post_reactions.user_id AS user_id',
            DB::raw(
                '(
            CASE
                WHEN user_id

                THEN (Select COUNT(*) from post_reactions as pr where pr.user_id = post_reactions.user_id AND ' . $genderWhere . ' ' . $countryWhere . ' ' . $ageWhere . '  DATE_FORMAT(post_reactions.created_at, "' . $dateFormate . '") = ' . $pivot . ')
            END
        ) as `count`'
            ),
            DB::raw(
                "(
                CASE
                    WHEN (SELECT `count`) < 10
                    THEN '<10'

                    WHEN (SELECT `count`) >= 10 AND (SELECT `count`) < 49
                    THEN '10-49'

                    WHEN (SELECT `count`) >= 50 AND (SELECT `count`) < 99
                    THEN '50-99'

                    WHEN (SELECT `count`) >= 100
                    THEN '100+'
                   END
            ) as `range`"
            ),
            DB::raw('(Select COUNT(DISTINCT(pr.user_id)) from post_reactions as pr where pr.user_id = post_reactions.user_id AND ' . $genderWhere . ' ' . $countryWhere . ' ' . $ageWhere . '  DATE_FORMAT(post_reactions.created_at, "' . $dateFormate . '") = ' . $pivot . ') as `users`')
        )
            ->where('users.status', '!=', Constants::ROLE_ADMIN)
            ->Join('users', 'users.id', '=', 'post_reactions.user_id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    $query->whereRaw('users.gender="' . $gender . '"');
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(DB::raw('DATE_FORMAT(post_reactions.created_at,  "' . $mapDate . '")'), '>=', $startDate)
            ->where(DB::raw('DATE_FORMAT(post_reactions.created_at,  "' . $mapDate . '")'), '<=', $endDate)
            ->groupBy(DB::raw('DATE_FORMAT(post_reactions.created_at, "' . $dateFormate . '")'), 'count')
            ->get();

        $groupedArray = [];
        $getUsers = $getUsers->toArray();
        foreach($getUsers as $user)
        {
            if(!array_key_exists($user[$pivot],$groupedArray))
            {
                $groupedArray[$user[$pivot]][$user['range']] = $user;
                continue;
            }else if(!array_key_exists($user['range'],$groupedArray[$user[$pivot]]))
            {
                $groupedArray[$user[$pivot]][$user['range']] = $user;
            }else
            {
                $groupedArray[$user[$pivot]][$user['range']]['count'] += $user['count'];
                $groupedArray[$user[$pivot]][$user['range']]['users'] += $user['users'];
            }

        }

        return $groupedArray;
    }
}
