<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\config\Constants;
use DB;
use FFMpeg;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\fileExists;

class Post extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_topic_id',
        'country_id',
        'title',
        'score',
        'file_url',
        'thumb_url',
        'media_type',
        'post_like_count',
        'type',
        'click',
        'seen_count',
        'status',
        'reason',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    protected $table = 'posts';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /*
    * This method use for get post status.
    */
    public function getStatusAttribute($value)
    {
        $status = '';
        switch ($value) {
            case 1:
                $status =  'active';
                break;
            case 2:
                $status = 'hidden';
                break;
            case 3:
                $status = 'reported';
                break;
            case 4:
                $status = 'removed';
                break;
            case 5:
                $status = 'suspended';
                break;
            case 6:
                $status = 'released';
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }

    public function getFileUrlAttribute($value)
    {
        $appEnviro = env('APP_ENV');
        $basePath = '';
        switch ($appEnviro) {
            case 'local':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'production':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'staging':
                $basePath = env('AWS_URL') . $value;
                break;

            default:
                $basePath = '';
                break;
        }
        return $basePath;
    }

    public function getThumbUrlAttribute($value)
    {
        $appEnviro = env('APP_ENV');
        $basePath = '';
        switch ($appEnviro) {
            case 'local':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'production':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'staging':
                $basePath = env('AWS_URL') . $value;
                break;

            default:
                $basePath = '';
                break;
        }
        return $basePath;
    }

    /*
    * This method use for get start age end age by age filter.
    */
    public function getUserAgeIdAttribute($value)
    {
        $start_age = '';
        $end_age = '';
        switch ($value) {
            case "18":
                $start_age = 1;
                $end_age   = 18;
                break;
            case "19-23":
                $start_age = 19;
                $end_age   = 23;
                break;
            case "24-30":
                $start_age = 24;
                $end_age   = 30;
                break;
            case "31-40":
                $start_age = 31;
                $end_age   = 40;
                break;
            case "41-50":
                $start_age = 41;
                $end_age   = 50;
                break;
            case "50":
                $start_age = 51;
                $end_age   = 100;
                break;
            default:
                $start_age = null;
                $end_age   = null;
                break;
        }
        return array("start_age" => $start_age, "end_age" => $end_age);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'post_reactions');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postTopic()
    {
        return $this->belongsTo(Topic::class, 'user_topic_id');
    }

    public function postRanking()
    {
        return $this->hasOne(PostRanking::class, 'post_id');
    }

    public function postCountry()
    {
        return $this->belongsTo(country::class, 'country_id');
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'post_id');
    }

    public function suspiciousActivity()
    {
        return $this->belongsTo(SuspiciousActivity::class,'post_id');
    }


    /**
     * Posts : Create Posts
     */
    public function createPost($data)
    {
        $user   =   Post::create($data);
        return $user ? $user : [];
    }

    /**
     * Posts : update post
     */
    public function updatePost($id, $data)
    {
        $post =  Post::find($id);
        $post->update($data);
        $post->save();
        return $post ? $post : array();
    }

    public function updateAllPost($userId, $data)
    {
        $records    =   Post::where('user_id',$userId)->update($data);
        return $records ? $records : array();
    }

    /**
     * Posts : Get LuckyDip Post
     */
    public function getLuckyPosts($favouritePostId, $user_id, $location, $topic, $start_date, $end_date)
    {
        $favouriteScore = "(SELECT posts.score FROM post_reactions JOIN posts on posts.id = post_reactions.post_like_id WHERE post_reactions.status = " . Constants::STATUS_ACTIVE . " AND post_reactions.user_id = $user_id ORDER BY post_reactions.id DESC LIMIT 1)";
        $posts =  $posts = Post::select(
            'posts.id as post_id',
            'posts.user_id as author_id',
            'posts.user_topic_id as topic_id',
            'posts.country_id as location_id',
            'posts.title',
            'posts.media_type',
            'posts.file_url',
            'posts.thumb_url',
            'posts.score',
            'post_reactions.status as like_dislike',
            'posts.status',
            DB::raw('DATEDIFF(CURRENT_DATE,posts.created_at) as age'),
            DB::raw('within_ten(posts.score,' . $favouriteScore . ') AS within_ten'),
            DB::raw('low_score(posts.score) AS low_score'),
            DB::raw('get_age_new(DATEDIFF(CURRENT_DATE,posts.created_at)) as age_new'),
            DB::raw('get_age_old(DATEDIFF(CURRENT_DATE,posts.created_at)) as age_old'),
            DB::raw('(select within_ten*age_new*age_old*low_score) as weightage'),
            'posts.created_at'
        )
            // ->leftJoin('post_reactions', 'posts.id', '=', 'post_reactions.post_dislike_id')
            ->leftJoin('post_reactions', function ($join) {
                $join->on('posts.id', '=', 'post_reactions.post_dislike_id');
                $join->on('posts.id', '=', 'post_reactions.post_like_id');
            })
            ->leftJoin('countries', 'countries.id', '=', 'posts.country_id')
            ->where('posts.id', '!=', $favouritePostId)
            ->whereNotExists(function ($query) use ($user_id) {
                $query->select('posts.id')
                    ->from('post_reactions')
                    ->whereRaw('posts.user_id = ' . $user_id)
                    ->whereRaw('posts.id = post_reactions.post_dislike_id')
                    ->orWhereRaw('posts.id = post_reactions.post_like_id');
            })
            ->where(function ($query) use ($location) {
                if ($location) {
                    $query->where('posts.country_id', $location);
                }
            })
            ->where(function ($query) use ($topic) {
                if ($topic) {
                    $query->where('posts.user_topic_id', $topic);
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date && $end_date) {
                    $query->whereBetween('posts.created_at', [$start_date, $end_date]);
                }
            })
            ->orderBy('weightage', 'desc')
            ->limit(1)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        } else {
            return $posts;
        };
    }

    /**
     * Posts : Get Follow Post
     */
    public function getFollowPosts()
    {

        $posts = Post::all();
        return ($posts) ? $posts : [];
    }

    /*
    /*
    * This method is used for get favourite post.
    */
    public function getFavouritePost($userId)
    {
        $post = Post::where('post_reactions.status', '=', '1')
            ->where('post_reactions.user_id', '=', $userId)
            ->leftJoin('post_reactions', 'posts.id', '=', 'post_reactions.post_like_id')
            ->select('posts.id as post_id', 'posts.user_id as author_id', 'posts.user_topic_id as topic_id', 'posts.country_id as location_id', 'posts.title', 'posts.media_type', 'posts.file_url', 'posts.thumb_url', 'posts.score', 'posts.status', 'posts.created_at')
            ->take(1)
            ->orderBy('post_reactions.id', 'DESC')
            ->first();


        if (!$post) {
            return null;
        } else {
            return $post;
        };
    }

    /**
     * Posts : Get Initial Posts
     */
    public function getInitialPosts($favouritePostId, $user_id, $location = null, $topic, $startDate, $endDate, $age, $followPost, $altId = null)
    {
        $favouriteScore = DB::select("SELECT CAST(posts.score as UNSIGNED) as score FROM post_reactions JOIN posts on posts.id = post_reactions.post_like_id WHERE post_reactions.status = " . Constants::STATUS_ACTIVE . " AND post_reactions.user_id = $user_id ORDER BY post_reactions.id DESC LIMIT 1");
        $set = (count($favouriteScore) > 0) ? (int)($favouriteScore[0]->score) : 0;
        $limit = $favouritePostId ? 1 : 2;

        if ($altId == null) {
            return $this->getQueryPost($set, $followPost, $user_id, $favouritePostId, $location, $topic, $startDate, $endDate, $limit, $age);
        } else if ($altId != null && $limit == 1) {
            $post = Post::whereDoesntHave('postLikeReactions', function ($query) use ($user_id) {
                $query->whereRaw('user_id = ' . $user_id);
            })
                ->WhereDoesntHave('postDislikeReactions', function ($query) use ($user_id) {
                    $query->whereRaw('user_id = ' . $user_id);
                })
                ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED, Constants::POSTS_STATUS_REPORTED])
                ->leftJoin('default_topics', 'posts.user_topic_id', 'default_topics.id')
                ->where('default_topics.status', Constants::STATUS_ACTIVE)
                ->whereHas('postCountry', function ($query) {
                    $query->where('status', Constants::COUNTRY_TYPE_ACTIVE);
                })
                ->where(function ($query) use ($age) {
                    if ($age <= 18) {
                        $query->where('default_topics.is_age_limit', Constants::STATUS_FALSE);
                    }
                })
                ->where('posts.id', $altId)->select(
                    'posts.id as post_id',
                    'posts.user_id as author_id',
                    'posts.user_topic_id as topic_id',
                    'posts.country_id as location_id',
                    'posts.title',
                    'posts.media_type',
                    'posts.file_url',
                    'posts.thumb_url',
                    'posts.score',
                    // 'post_reactions.status as like_dislike',
                    'posts.status',
                    'posts.created_at'
                )->get();
            return $post;
        } else if ($altId != null && $limit == 2) {
            $selectedPost = Post::whereDoesntHave('postLikeReactions', function ($query) use ($user_id) {
                $query->whereRaw('user_id = ' . $user_id);
            })
                ->WhereDoesntHave('postDislikeReactions', function ($query) use ($user_id) {
                    $query->whereRaw('user_id = ' . $user_id);
                })
                ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED, Constants::POSTS_STATUS_REPORTED])
                ->leftJoin('default_topics', 'posts.user_topic_id', 'default_topics.id')
                ->where('default_topics.status', Constants::STATUS_ACTIVE)
                ->whereHas('postCountry', function ($query) {
                    $query->where('status', Constants::COUNTRY_TYPE_ACTIVE);
                })
                ->where(function ($query) use ($age) {
                    if ($age <= 18) {
                        $query->where('default_topics.is_age_limit', Constants::STATUS_FALSE);
                    }
                })
                ->where('posts.id', $altId)->select(
                    'posts.id as post_id',
                    'posts.user_id as author_id',
                    'posts.user_topic_id as topic_id',
                    'posts.country_id as location_id',
                    'posts.title',
                    'posts.media_type',
                    'posts.file_url',
                    'posts.thumb_url',
                    'posts.score',
                    'posts.status',
                    'posts.created_at'
                )->get()->toArray();
            $limit  =   1;
            $alternativePost    =    $this->getQueryPost($set, $followPost, $user_id, $favouritePostId, $location, $topic, $startDate, $endDate, $limit, $age)->toArray();
            $alternativePost = $alternativePost ? $alternativePost[0] : $alternativePost;
            array_push($selectedPost, $alternativePost);
            return $post    =  $selectedPost;
        }
    }

    public function postLikeReactions()
    {
        return $this->hasMany(PostReaction::class, 'post_like_id');
    }

    public function postDislikeReactions()
    {
        return $this->hasMany(PostReaction::class, 'post_dislike_id');
    }

    /*
    * This method is use for get no of post live reported hidden.
    */
    public function getNoOfPost($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('posts.status', DB::raw('COUNT(*) AS post_count'))
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_REPORTED, Constants::POSTS_TYPE_HIDDEN, Constants::POSTS_TYPE_REMOVED ,  Constants::POSTS_TYPE_SUSPENDED])
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(['posts.status'])
            ->get();


        $activeCount    = 0;
        $reportedCount  = 0;
        $hiddenCount    = 0;
        foreach ($getPost as $post) {
            if ($post['status'] == Constants::POST_STATUS_ACTIVE) {
                $activeCount  = $post['post_count'];
            }

            if ($post['status'] == Constants::POST_STATUS_REPORTED) {
                $reportedCount  = $post['post_count'];
            }

            if ($post['status'] == Constants::POST_STATUS_HIDDEN || $post['status'] == Constants::POSTS_STATUS_REMOVED || $post['status'] == Constants::POSTS_STATUS_SUSPENDED) {
                $hiddenCount  += $post['post_count'];
            }
        }
        return array("active_post_count" => $activeCount, "reported_count" => $reportedCount, "hidden_count" => $hiddenCount);
    }
    /*
    * This method is use for get no of post by topic.
    */
    public function getNoOfPostByTopic($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('posts.user_topic_id', DB::raw('COUNT(*) AS post_count'), 'default_topics.name', 'posts.created_at')
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('default_topics', 'posts.user_topic_id', '=', 'default_topics.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(posts.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(DB::raw('DATE_FORMAT(posts.created_at, "%d")'), 'posts.user_topic_id')
            ->orderBy(DB::raw('DATE(posts.created_at)'), 'ASC')
            ->get();

        $result = [];
        $date = [];
        $groupedArray = [];
        foreach ($getPost as $post) {
            $result['user_topic_id'] = $post['user_topic_id'];
            $result['topic_name'] = $post['name'];
            $result['post_count'] = $post['post_count'];
            $result['created_at'] = explode(" ", $post['created_at'])[0];

            $date = date('Y-m-d', strtotime($result['created_at']));
            $groupedArray[$date][] = $result;
        }
        return $groupedArray;
    }
    /*
    * This method is use for get no of post in topic.
    */
    public function getNoOfPostInTopic($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {

        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('posts.user_topic_id', 'posts.status', DB::raw('COUNT(*) AS post_count'), 'default_topics.name', 'posts.created_at')
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_REPORTED, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED])
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('default_topics', 'posts.user_topic_id', '=', 'default_topics.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(['posts.status', 'posts.user_topic_id'])
            ->get();

        $result = [];
        $groupedArray = [];
        $date = [];
        foreach ($getPost as $post) {
            $result['topic_id']   = $post['user_topic_id'];
            $result['topic_name'] = $post['name'];
            $result['post_count'] = $post['post_count'];
            $result['status']     = $post['status'];
            $result['created_at'] = explode(" ", $post['created_at'])[0];

            $date = date('Y-m', strtotime($result['created_at']));
            $groupedArray[$result['topic_name']][] = $result;
        }

        return $groupedArray;
    }
    /*
    * This method is use for get no of reported removed posts.
    */
    public function getPostReports($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('posts.user_topic_id', 'posts.status', 'posts.created_at', 'posts.updated_at', DB::raw('COUNT(*) AS post_count'))
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->whereIn('posts.status', [Constants::POSTS_TYPE_REPORTED, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED])
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(posts.updated_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy([DB::raw("DATE_FORMAT(`posts`.`updated_at`, '%Y-%m-%d')"), 'posts.status'])
            ->orderBy(DB::raw("DATE_FORMAT(`posts`.`updated_at`, '%Y-%m-%d')"),'asc')
            ->get();
        $result  = [];
        $date    = [];
        $groupedArray = [];
        foreach ($getPost->toArray() as $post) {

            $result['post_count'] = $post['post_count'];
            $result['status']     = $post['status'];
            $result['updated_at'] = explode(" ", $post['updated_at'])[0];
            $date = date('Y-m-d', strtotime($result['updated_at']));

            $groupedArray[$date][] = $result;
        }

        return $groupedArray;
    }
    /*
    * This method is use for get no of reported posts.
    */
    public function getNoOfReportedPost($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('posts.user_topic_id', 'posts.created_at', 'posts.updated_at','posts.status', DB::raw('COUNT(*) AS post_count'))
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->whereIn('posts.status', [Constants::POSTS_TYPE_SUSPENDED, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_RELEASED])
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(posts.updated_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy([DB::raw("DATE_FORMAT(`posts`.`updated_at`, '%Y-%m-%d')"), 'posts.status'])
            ->orderBy('posts.updated_at', 'asc')
            ->get();

        $result  = [];
        $monthly = [];
        $groupedArray = [];
        foreach ($getPost as $post) {

            $result['post_count'] = $post['post_count'];
            $result['status']     = $post['status'];
            $result['updated_at'] = explode(" ", $post['updated_at'])[0];
            $monthly = $result['updated_at'];
            $monthly = date('Y-m-d', strtotime($result['updated_at']));
            $groupedArray[$monthly][] = $result;
        }

        return $groupedArray;
    }
    /*
    * This method is use for get no of posts by topic.
    */
    public function getNoOfPostsByTopic($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('default_topics.id as default_topic_id', 'posts.user_topic_id', 'posts.created_at', 'posts.status', DB::raw('COUNT(*) AS post_count'), 'default_topics.name')
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('default_topics', 'posts.user_topic_id', '=', 'default_topics.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('posts.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(['posts.user_topic_id'])
            ->orderBy('posts.created_at', 'asc')
            ->get();

        $result  = [];
        $topic = [];
        $groupedArray = [];
        $i = 0;
        foreach ($getPost as $post) {

            $result[$i]['topic_id']   = $post['default_topic_id'];
            $result[$i]['topic_name'] = $post['name'];
            $result[$i]['post_count'] = $post['post_count'];
            $result[$i]['created_at'] = explode(" ", $post['created_at'])[0];
            $i++;
        }

        return $result;
    }
    /*
    * This method is use for get no of posts by country.
    */
    public function getNoOfPostsByCountry($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getPost = Post::select('countries.id as country_id', 'posts.created_at', 'posts.status', DB::raw('COUNT(*) AS post_count'), 'countries.name')
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('countries', 'posts.country_id', '=', 'countries.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('posts.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(['posts.country_id'])
            ->orderBy('posts.created_at', 'asc')
            ->get();

        $result  = [];
        $country = [];
        $i = 0;
        foreach ($getPost as $post) {

            $result[$i]['country_id']   = $post['country_id'];
            $result[$i]['country_name'] = $post['name'];
            $result[$i]['post_count']   = $post['post_count'];
            $result[$i]['created_at']   = explode(" ", $post['created_at'])[0];
            $i++;
        }

        return $result;
    }
    /*
    * This method is use for get no of posts selected alternative/favourite.
    */
    public function getNoOfPostsSelected($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getFavouritePost = Post::select('posts.id', 'posts.status', DB::raw('COUNT(*) AS post_count'))
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('post_reactions', 'posts.id', '=', 'post_reactions.id')
            ->whereExists(function ($query) {
                $query->select('posts.id')
                    ->from('post_reactions')
                    ->whereRaw('posts.id = post_reactions.post_like_id');
            })
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->orderBy('posts.created_at', 'asc')
            ->count();

        $getOtherPost = Post::select('posts.id', 'posts.status', DB::raw('COUNT(*) AS post_count'))
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('post_reactions', 'posts.id', '=', 'post_reactions.id')
            ->whereNotExists(function ($query) {
                $query->select('posts.id')
                    ->from('post_reactions')
                    ->whereRaw('posts.id = post_reactions.post_like_id');
            })
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    $query->where('users.gender', $gender);
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->orderBy('posts.created_at', 'asc')
            ->count();

        $result = array("favourite_count" => $getFavouritePost, "alternative_post" => $getOtherPost);

        return $result;
    }
    /**
     * This method is used to store videos of posts
     */
    public function uploadPostVideoFile($file)
    {
        $user      = Auth::user();
        $image     = $file;
        $extension = $image->getClientOriginalExtension();
        $ImgName   = $image->getClientOriginalName();
        $ImgName   = str_replace(" ", "_", $ImgName);
        $time      = time();
        $fileNameWithoutEx = pathinfo($ImgName, PATHINFO_FILENAME);
        $imageName = $fileNameWithoutEx . "_" . $time . "." . $extension;
        $basePath      = "user_images/user_" . $user->id . "";
        $destinationPath = public_path($basePath);
        if (!file_exists($destinationPath)) {
            //create folder
            mkdir(public_path($basePath), 0777, true);
        }
        $basePath      = "user_images/user_" . $user->id . "";
        $destinationPath = $basePath . $imageName;
        // $destinationPath = base_path('public/user_images/user_' . $userId . '');
        $pathForVideoThumbnails = public_path($basePath) . '/thumb_' . $fileNameWithoutEx . "_" . $time . "." . 'jpg';
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 36000000000000000000000, // The timeout for the underlying process
            'ffmpeg.threads'   => 16,   // The number of threads that FFMpeg should use
        ]);
        $video = $ffmpeg->open($image);
        $video
            ->filters()
            ->resize(new FFMpeg\Coordinate\Dimension(320, 240))
            ->synchronize();
        // $video
        //     ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
        //     ->save('frame.jpg');
        // $video = $ffmpeg->open($image);
        $ffprobe = ffmpeg\FFProbe::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 36000000000000000000000, // The timeout for the underlying process
            'ffmpeg.threads'   => 16,   // The number of threads that FFMpeg should use
        ]);
        $duration = $ffprobe
            ->format($file) // extracts file informations
            ->get('duration');

        if ($duration > 15.99) {
            return false;
        }

        $secToCut   =   $duration / 2;

        $video
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($secToCut))
            ->save($pathForVideoThumbnails);


        // MOV video file extension convert into mp4
        $image->move(public_path($basePath), $fileNameWithoutEx . "_" . $time . "." . $extension);
        $pathFormp4File = public_path($basePath) . '/' . $fileNameWithoutEx . "_" . $time . "." . $extension;
        $convertedFileName = public_path($basePath) . '/' . $fileNameWithoutEx . "_" . $time;

        \shell_exec("ffmpeg -i $pathFormp4File $convertedFileName.mp4");
        $videoName = 'user_images/user_' . $user->id . '/' . $fileNameWithoutEx . "_" . $time . ".mp4";
        $imageName = 'user_images/user_' . $user->id . '/thumb_' . $fileNameWithoutEx . "_" . $time . ".jpg";
        if (env('APP_ENV') == "production") {

            $thumbS3Storage = Storage::disk('s3')->put($imageName, \fopen($pathForVideoThumbnails, 'r+'));
            $imageS3Storage = Storage::disk('s3')->put($videoName, file_get_contents($convertedFileName . ".mp4"));

            //if stored to s3 delete from local directory
            if ($thumbS3Storage) {
                unlink($pathForVideoThumbnails);
                if ($imageS3Storage) {
                    unlink($pathFormp4File);
                }
            }

            $temp[] = array(
                'full_path' => env('AWS_URL') . $pathFormp4File,
                'thumb_path' => env('AWS_URL') . $convertedFileName
            );
        }
        $file_paths = [
            'video_thumb'   =>  $imageName,
            'video_url'     =>  $videoName
        ];
        return $file_paths;
    }
    /*
    * This method is use for get no of posts selected alternative/favourite.
    */
    public function getInteraction($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getFavPosts = Post::select(
            'post_reactions.created_at',
            DB::raw('COUNT(IF(posts.type = "post", post_reactions.post_like_id, 0)) AS post_favourite_count')
        )
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->Join('users', 'users.id', '=', 'posts.user_id')
            ->Join('post_reactions', 'post_reactions.post_like_id', '=', 'posts.id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(DB::raw('DATE_FORMAT(post_reactions.created_at, "%m")'))
            ->get()
            ->toArray();

        $getSeens = Post::select(
            'posts.updated_at AS created_at',
            DB::raw('SUM(IF(posts.type = "post", posts.seen_count, 0)) AS post_seen_count'),
            DB::raw('SUM(IF(posts.type = "post", posts.click, 0)) AS post_click_count'),
            DB::raw('SUM(IF(posts.type = "advertisement", posts.seen_count, 0)) AS ads_seen_count')
        )
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->Join('users', 'users.id', '=', 'posts.user_id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    $query->where('users.gender', '=', $gender);
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(users.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(DB::raw('DATE_FORMAT(posts.updated_at, "%m")'))
            ->get()
            ->toArray();

        $arrayMerge = array_merge($getFavPosts, $getSeens);

        $i = 0;
        $merge = [];
        foreach ($arrayMerge as $groupMonthly) {
            $m = 0;
            for ($y = 0; $y < $i; $y++) {
                if (date("m", strtotime($merge[$y]['created_at'])) == date("m", strtotime($groupMonthly['created_at']))) {
                    $merge[$y]['post_favourite_count']  += (array_key_exists('post_favourite_count', $groupMonthly)) ? $groupMonthly['post_favourite_count'] : 0;
                    $merge[$y]['post_seen_count']       += (array_key_exists('post_seen_count', $groupMonthly)) ? $groupMonthly['post_seen_count'] : 0;
                    $merge[$y]['post_click_count']      += (array_key_exists('post_click_count', $groupMonthly)) ? $groupMonthly['post_click_count'] : 0;
                    $merge[$y]['ads_seen_count']        += (array_key_exists('ads_seen_count', $groupMonthly)) ? $groupMonthly['ads_seen_count'] : 0;
                    $m++;
                }
            }
            if ($m != 0) {
                continue;
            }

            $merge[$i]['post_favourite_count'] = (array_key_exists('post_favourite_count', $groupMonthly)) ? $groupMonthly['post_favourite_count'] : 0;
            $merge[$i]['post_seen_count']      = (array_key_exists('post_seen_count', $groupMonthly)) ? $groupMonthly['post_seen_count'] : 0;
            $merge[$i]['post_click_count']     = (array_key_exists('post_click_count', $groupMonthly)) ? $groupMonthly['post_click_count'] : 0;
            $merge[$i]['ads_seen_count']       = (array_key_exists('ads_seen_count', $groupMonthly)) ? $groupMonthly['ads_seen_count'] : 0;
            $merge[$i]['created_at']           = $groupMonthly['created_at'];
            $i++;
        }

        $result = [];
        $groupedArray = [];
        if ($merge) {
            foreach ($merge as $posts) {
                $result['post_favourite_count'] = $posts['post_favourite_count'];
                $result['post_seen_count']      = $posts['post_seen_count'];
                $result['post_click_count']     = $posts['post_click_count'];
                $result['ads_seen_count']       = $posts['ads_seen_count'];
                $result['created_at']           = explode("T", $posts['created_at'])[0];
                $groupedArray[date('Y-m', strtotime($result['created_at']))][] = $result;
            }
        }

        return $groupedArray;
    }
    /*
    * This method is use for get post score monthly.
    */
    public function getPostScores($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $post      = new Post();
        $getAge    = $post->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getUsers =   Post::from('posts as p')
            ->select(
                'p.created_at',
                'p.id',
                DB::raw('(CASE WHEN p.score <= 50 THEN(SELECT COUNT(id) FROM posts WHERE posts.score <= 50 AND DATE_FORMAT(created_at, "%m") = DATE_FORMAT(p.created_at, "%m")) WHEN p.score >= 51 AND p.score <= 70 THEN(SELECT COUNT(id) FROM posts WHERE posts.score >= 51 AND posts.score <= 70 AND DATE_FORMAT(created_at, "%m") = DATE_FORMAT(p.created_at, "%m")) WHEN p.score >= 71 AND p.score <= 80 THEN(SELECT COUNT(id) FROM posts WHERE posts.score >= 71 AND posts.score <= 80 AND DATE_FORMAT(created_at, "%m") = DATE_FORMAT(p.created_at, "%m")) WHEN p.score >= 81 AND p.score <= 90 THEN(SELECT COUNT(id) FROM posts WHERE posts.score >= 81 AND posts.score <= 90 AND DATE_FORMAT(created_at, "%m") = DATE_FORMAT(p.created_at, "%m")) WHEN p.score >= 91 AND p.score <= 100 THEN(SELECT COUNT(id) FROM posts WHERE posts.score >= 91 AND posts.score <= 100 AND DATE_FORMAT(created_at, "%m") = DATE_FORMAT(p.created_at, "%m")) END ) AS "post_count"'),
                DB::raw('(CASE WHEN p.score <= 50 THEN "<50" WHEN p.score >= 51 AND p.score <= 70 THEN "51-70" WHEN p.score >= 71 AND p.score <= 80 THEN "71-80" WHEN p.score >= 81 AND p.score <= 90 THEN "81-90" WHEN p.score >= 91 AND p.score <= 100 THEN "91-100" END) AS "range"')
            )
            ->where('users.role_id', '!=', Constants::ROLE_ADMIN)
            ->Join('users', 'users.id', '=', 'p.user_id')
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('users.country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if ($gender == "others") {
                    //     $query->whereIn('gender', ['non-binary', 'prefer not to say']);
                    // } else {
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),users.dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(p.created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy('range', DB::raw('DATE_FORMAT(p.created_at, "%m")'))
            ->orderBy('p.created_at', "ASC")
            ->get();

        $result = [];
        $groupedArray = [];
        foreach ($getUsers as $activity) {
            if ($activity['post_count'] == 0) {
                continue;
            }
            $result['post_count']  = $activity['post_count'];
            $result['created_at']  = explode("T", $activity['created_at'])[0];
            $groupedArray[date('Y-m', strtotime($result['created_at']))][$activity['range']][] = $result;
        }

        return $groupedArray;
    }



    /**
     * This method is used to get scores of a post using id
     */
    public function getPostScore($id)
    {
        $postScore   =   Post::select('posts.score as score')->where('posts.id', $id)->first();
        return $postScore ? $postScore->score : 0;
    }

    /**
     * This method is used to update score of post based on like and disklike
     */
    public function updatePostScores($likeId, $dislikeId, $user)
    {

        /**
         * Getting admin latest configurations
         */
        $dislikeId = (string) $dislikeId;
        $dislikeId = (string) $dislikeId;
        $adminConfigModel   =   new AdminConfig();
        $followerWeightage  =   $adminConfigModel->getSpecificConfig('followers')['value'];
        $repeatWeight       =   $adminConfigModel->getSpecificConfig('repeat_weight')['value'];
        $repeatLimit        =   $adminConfigModel->getSpecificConfig('repeat_limit')['value'];
        $ageGtSeven         =   $adminConfigModel->getSpecificConfig('age_gt_7')['value'];
        $ageGtNine          =   $adminConfigModel->getSpecificConfig('age_gt_29')['value'];
        $ageGtHund          =   $adminConfigModel->getSpecificConfig('age_gt_100')['value'];
        $newAge             =   (string) $adminConfigModel->getSpecificConfig('new_age')['value'];
        $age_post_days_new  =   $adminConfigModel->getSpecificConfig('new_post_days')['value'];
        $age_post_days_old  =   $adminConfigModel->getSpecificConfig('post_retention_period')['value'];



        //this query is trigering post score basic update mysql function this function will get ids and score and then perform algo and update the scores
        $basicScore = DB::select('select post_score_updater(' . $likeId . ',' . $this->getPostScore($likeId) . ',' . $dislikeId . ',' . $this->getPostScore($dislikeId) . ') as flag');
        $basicScore = (array)$basicScore[0];
        $basicScore = $basicScore['flag'];
        $basicScore =  explode(',', $basicScore);
        $basicLikeScore = $basicScore[0];
        $basicDisLikeScore = $basicScore[1];
        //this query is used to get last updated scores and update according to follow algorithm
        $postUser       =   Post::find($likeId);
        $isFollower     =   $user->follows()->where('other_user_id', $postUser->user_id)->get();
        $isFollower     =   isset($isFollower[0]) ? 1 : 0;
        $followerScore  =   DB::select('select post_follower_score_updater(' . $likeId . ',' . $this->getPostScore($likeId) . ',' . $dislikeId . ',' . $this->getPostScore($dislikeId) . ',' . $isFollower . ',' . $followerWeightage . ') as flag');
        $followerScore = (array)$followerScore[0];
        $followerScore = $followerScore['flag'];
        $followerScore =  explode(',', $followerScore);
        $followerLikeScore = $followerScore[0];
        $followerDisLikeScore = $followerScore[1];


        //this query is used to get last updated scores and update according to repeat algorithm
        $repeatScoreCount    =   PostReaction::where(['post_like_id' => $likeId, 'user_id' => $user->id])->count();
        $repeatScore         =   DB::select('select post_repeat_score_updater(' . $likeId . ',' . $this->getPostScore($likeId) . ',' . $dislikeId . ',' . $this->getPostScore($dislikeId) . ',' . $repeatScoreCount . ',' . $repeatWeight . ',' . $repeatLimit . ') as flag');
        $repeatScore = (array)$repeatScore[0];
        $repeatScore = $repeatScore['flag'];
        $repeatScore = $repeatScore == null ? '1,-1' : $repeatScore;
        $repeatScore =  explode(',', $repeatScore);
        $repeatLikeScore = $repeatScore[0];
        $repeatDisLikeScore = $repeatScore[1];

        //this query is used to get last updated scores and update according to age algorithm

        //calculating like age
        $likeAge            =  Post::select(
            DB::raw('DATEDIFF(CURRENT_DATE,posts.created_at) as likeAge')
        )->where('posts.id', $likeId)->first()->only('likeAge')['likeAge'];

        //calculating dislike age
        $dislikeAge         =  (string) Post::select(
            DB::raw('DATEDIFF(CURRENT_DATE,posts.created_at) as disLikeAge')
        )->where('posts.id', $dislikeId)->first()->only('disLikeAge')['disLikeAge'];

        $ageScore           =   DB::select('select post_age_score_updater(' . $likeId . ',' . $this->getPostScore($likeId) . ',' . $dislikeId . ',' . $this->getPostScore($dislikeId) . ',' . $likeAge . ',' . $dislikeAge . ',' . $age_post_days_old . ',' . $age_post_days_new . ',' . $ageGtSeven . ',' . $ageGtNine . ',' . $ageGtHund . ',' . $newAge . ') as flag');
        $ageScore = (array)$ageScore[0];
        $ageScore = $ageScore['flag'];
        $ageScore =  explode(',', $ageScore);
        $ageLikeScore = $ageScore[0];
        $ageDisLikeScore = $ageScore[1];

        $finalLikeScore = (string) ($basicLikeScore * $followerLikeScore * $repeatLikeScore * $ageLikeScore);
        $finalDisLikeScore = (string) ($basicDisLikeScore * $followerDisLikeScore * $repeatDisLikeScore * $ageDisLikeScore);
        $str1 = (float) $this->getPostScore($likeId);
        $str2 = (float) $this->getPostScore($dislikeId);

        $finalScore =   DB::select("select update_post_score($likeId,$str1,$dislikeId,$str2,$finalLikeScore,$finalDisLikeScore,$dislikeAge,$newAge) as flag");

        $finalScore = (array)$finalScore[0];
        $finalScore = $finalScore['flag'];
        $finalScore =  explode(',', $finalScore);
        $TotalLikeScore = (float)$finalScore[1];
        $TotalDisLikeScore = (float) $finalScore[0];

        //Now updating query
        $dataLike    =  DB::update("update posts set posts.score = $TotalLikeScore where posts.id=$likeId");
        $dataDislike =  DB::update("update posts set posts.score = $TotalDisLikeScore where posts.id=$dislikeId");

        return $finalScore;
    }


    /**
     * This method is used to get the post
     */
    public function getQueryPost($set, $followPost, $user_id, $favouritePostId, $location, $topic, $startDate, $endDate, $limit, $age)
    {
        //fetching latest post weightages
        $postWeightModel    =   new PostWeight();
        $adminConfigModel   =   new AdminConfig();
        $within_ten_value   =   $postWeightModel->getPostWeightageRule('within_10')['weight'];
        $score_less_twnty   =   $postWeightModel->getPostWeightageRule('score_lt_20')['weight'];
        $score_less_thirty  =   $postWeightModel->getPostWeightageRule('score_lt_30')['weight'];
        $score_less_forty   =   $postWeightModel->getPostWeightageRule('score_lt_40')['weight'];
        $score_less_fifty   =   $postWeightModel->getPostWeightageRule('score_lt_50')['weight'];
        $age_new            =   $postWeightModel->getPostWeightageRule('post_lt_y_days_old')['weight'];
        $age_old            =   $postWeightModel->getPostWeightageRule('post_gt_x_days_old')['weight'];
        $age_post_days_new  =   $adminConfigModel->getSpecificConfig('new_post_days')['value'];
        $age_post_days_old  =   $adminConfigModel->getSpecificConfig('post_retention_period')['value'];

        $posts = Post::select(
            'posts.id as post_id',
            'posts.user_id as author_id',
            'posts.user_topic_id as topic_id',
            'posts.country_id as location_id',
            'posts.title',
            'posts.media_type',
            'posts.file_url',
            'posts.thumb_url',
            'posts.score',
            'post_reactions.status as like_dislike',
            'posts.status',
            DB::raw('DATEDIFF(CURRENT_DATE,posts.created_at) as age'),
            DB::raw('within_ten(posts.score,' . $set . ',' . $within_ten_value . ') AS within_ten'),
            DB::raw('low_score(posts.score,' . $score_less_fifty . ',' . $score_less_forty . ',' . $score_less_thirty . ',' . $score_less_twnty . ') AS low_score'),
            DB::raw('get_age_new(DATEDIFF(CURRENT_DATE,posts.created_at),' . $age_post_days_new . ',' . $age_new . ') as age_new'),
            DB::raw('get_age_old(DATEDIFF(CURRENT_DATE,posts.created_at),' . $age_post_days_old . ',' . $age_old . ') as age_old'),
            DB::raw('(select within_ten*age_new*age_old*low_score) as weightage'),
            'posts.created_at'
        )
            // ->leftJoin('post_reactions', 'posts.id', '=', 'post_reactions.post_dislike_id')
            ->leftJoin('post_reactions', function ($join) {
                $join->on('posts.id', '=', 'post_reactions.post_dislike_id');
                $join->on('posts.id', '=', 'post_reactions.post_like_id');
            })
            ->leftJoin('default_topics', 'posts.user_topic_id', 'default_topics.id');


        //$newPosts = $posts;
        if ($followPost) {
            $posts->leftJoin('users', 'users.id', 'posts.user_id')
                ->leftJoin('user_followers', function ($join) {
                    $join->on('user_followers.user_id', 'users.id')
                        ->orWhereRaw('posts.user_id = user_followers.other_user_id');
                })
                ->whereRaw('posts.user_id = user_followers.other_user_id')
                ->whereRaw('user_followers.user_id = ' . $user_id);
            //dd($posts->dd());
        }

        $posts->whereRaw('posts.id != ' . $favouritePostId)
            ->whereNotExists(function ($query) use ($user_id) {
                $query->select('post_reactions.id')
                    ->from('post_reactions')
                    ->whereRaw('post_reactions.user_id = ' . $user_id)
                    ->whereRaw('post_reactions.post_dislike_id = posts.id')
                    ->orWhereRaw('post_reactions.post_like_id = posts.id');
            })
            ->where(function ($query) use ($location) {
                if ($location) {
                    $query->where('posts.country_id', $location);
                }
            })
            ->where(function ($query) use ($topic) {
                if ($topic) {
                    $query->whereIn('posts.user_topic_id', $topic);
                }
            })
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('posts.created_at', [$startDate, $endDate]);
                }
            });

        // ->inRandomOrder()
        $posts->orderBy('weightage', 'desc')
            ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED])
            ->where('default_topics.status', Constants::STATUS_ACTIVE)
            ->where(function ($query) use ($age) {
                if ($age <= 18) {
                    $query->where('default_topics.is_age_limit', Constants::STATUS_FALSE);
                }
            })
            ->whereHas('postCountry', function ($query) {
                $query->where('status', Constants::COUNTRY_TYPE_ACTIVE);
            })
            ->limit($limit);
        //->get();
        return $posts->get();
    }

    /**
     * this method is used to get top 100
     */
    public function getTopPosts($user, $topic = null, $startDate = null, $endDate = null)
    {
        $adminSettingModel  =   new AdminConfig();
        $postMinimumReach   = $adminSettingModel->getSpecificConfig(Constants::RULE_MINIMUM_REACH_POINTS)['value'];
        $posts  =   Post::with('user')
            ->where('posts.score', '>=', $postMinimumReach)
            ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED, Constants::POSTS_TYPE_REPORTED])
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
            ->whereHas('postCountry', function ($query) {
                $query->where('status', Constants::COUNTRY_TYPE_ACTIVE);
            })
            ->orderBy('posts.score', 'desc');
        return $posts;
    }

    /**
     * This method is used to get CMS records
     */
    public function getCmsRecords($offset, $limit, $sort, $filter, $like)
    {
        $data       =   Post::with('user')
            ->whereHas('user', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    if ($like) {
                        $query->where('username', 'like', '%' . $like . '%');
                    }
                });
            })
            ->withCount('reports')
            ->where(function ($query) use ($filter) {
                if ($filter) {
                    $query->whereIn('posts.status', $filter);
                }
            })
            ->orderBy('id', $sort);

        $count  =   $data->count();
        $data   =   $data->take($limit)->skip($offset)->get();
        $response = ['data' => $data, 'count' => $count];
        return $response;
    }
}
