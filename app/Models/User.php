<?php

namespace App\Models;

use App\config\Constants;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;
use DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'country_id',
        'city',
        'username',
        'email',
        'password',
        'gender',
        'profile_image',
        'thumb_image',
        'bio_details',
        'dob',
        'verification_code',
        'verify_code_expiry',
        'device_token',
        'platform',
        'is_notify',
        'status',
        'last_login',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'verify_code_expiry',
        'pivot'
    ];

    protected $table = 'users';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function postReactions()
    {
        return $this->hasMany(PostReaction::class);
    }

    public function country()
    {
        return $this->belongsTo(country::class);
    }

    public function userTopics()
    {
        return $this->belongsToMany(Topic::class, 'user_topics', 'user_id', 'def_topic_id')->withTimestamps();
    }

    //returns the user which login user follows
    public function follows()
    {
        return $this->belongsToMany(User::class, 'user_followers', 'user_id', 'other_user_id')->withTimestamps();
    }

    //returns the follower of the login user
    public function follower()
    {
        return $this->belongsToMany(User::class, 'user_followers', 'other_user_id', 'user_id')->withTimestamps();
    }

    public function post()
    {
        return $this->hasMany(Post::class);
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'other_user_id');
    }

    public function user_search()
    {
        return $this->belongsToMany(User::class, 'user_recent_searches','user_id','searchable_id')
        ->orderBy('user_recent_searches.id','desc')
        ->withTimestamps();
    }

    public function userSearchable()
    {
        return $this->belongsToMany(User::class, 'user_recent_searches', 'searchable_id','user_id')->withTimestamps();
    }

    public function getProfileImageAttribute($value)
    {
        $value = $value ? $value : 'user_images/dummy_image.png';
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

    public function getThumbImageAttribute($value)
    {
        $value = $value ? $value : 'user_images/dummy_image.png';
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

    public function getRoleIdAttribute($value)
    {
        $roleName = '';
        switch ($value) {
            case 1:
                $roleName =  'user';
                break;
            case 2:
                $roleName = 'admin';
                break;
            case 3:
                $roleName = 'sub-admin';
                break;
            default:
                $roleName = '';
                break;
        }
        return $roleName;
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

    public function getStatusAttribute($value)
    {
        $roleName = '';
        switch ($value) {
            case 0:
                $roleName =  'un_verified';
                break;
            case 1:
                $roleName = 'active';
                break;
            case 2:
                $roleName = 'in_active';
                break;
            case 3:
                $roleName = 'banned';
                break;
            case 4:
                $roleName = 'profile_incomplete';
                break;
            case 5:
                $roleName = 'user_details_incomplete';
                break;
            case 6:
                $roleName = 'admin_permmision_required';
                break;
            case 7:
                $roleName = 'profile_incomplete_admin_permmision_required';
                break;
            case 8:
                $roleName = 'deleted';
                break;
            default:
                $roleName = '';
                break;
        }
        return $roleName;
    }

    public function getIsNotifyAttribute($value)
    {
        $notification = '';
        switch ($value) {
            case 1:
                $notification =  'active';
                break;
            case 0:
                $notification = 'in-active';
                break;
            default:
                $notification = '';
                break;
        }
        return $notification;
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function createUser($data)
    {
        $newAccount = User::create($data);
        return $newAccount ? $newAccount :  array();
    }

    public function updateUser($id, $data)
    {
        $user =  User::find($id);
        $user->update($data);
        $user->save();
        return $user ? $user : array();
    }

    public function getUniqueUsername($user, $request)
    {
        $users = User::select('id', 'username')
            ->where('username', $request->input('username'))
            ->first();
        return $users;
    }
    /*
    * This method is use for get total users count.
    */
    public function getTotalUsersCount($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getUsers = User::select('created_at', 'status')
            ->where('role_id', '!=', Constants::ROLE_ADMIN)
            ->whereIn('status', [Constants::USER_STATUS_ACTIVE_INT, Constants::USER_STATUS_INACTIVE_INT])
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if($gender == "others"){
                    //     $query->whereIn('gender', ['non-binary','prefer not to say']);
                    // }else{
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(created_at)'), array($start_date, $end_date));
                }
            })
            ->get();

        $result = [];
        $active_user  = [];
        $groupedArray = [];
        $activeCount = 0;
        $inActiveCount = 0;
        foreach ($getUsers as $user) {
            $activeCount   += ($user['status'] == "active") ?  1 : 0;
            $inActiveCount += ($user['status'] == "in_active") ?  1 : 0;
            $result['status']     = $user['status'];
            $result['created_at'] = explode(" ", $user['created_at'])[0];
            $active_user = date('Y-m-d', strtotime($result['created_at']));
            $groupedArray[$active_user][] = $result;
        }

        return array('active_users' => $activeCount, 'inactive_users' => $inActiveCount, 'no_of_users' => $groupedArray);
    }
    /*
    * This method is use for get total users count.
    */
    public function getTotalDownloads($country = [], $gender = null, $age = null, $start_date = null, $end_date = null)
    {
        $getAge = $this->getUserAgeIdAttribute($age);
        $start_age = $getAge['start_age'];
        $end_age   = $getAge['end_age'];

        $getUsers = User::select('created_at', DB::raw('COUNT(*) AS download_count'), 'platform')
            ->where('role_id', '!=', Constants::ROLE_ADMIN)
            ->whereIn('platform', ['android', 'ios'])
            ->where(function ($query) use ($country) {
                if ($country) {
                    $query->whereIn('country_id', $country);
                }
            })
            ->where(function ($query) use ($gender) {
                if ($gender) {
                    // if($gender == "others"){
                    //     $query->whereIn('gender', ['non-binary','prefer not to say']);
                    // }else{
                    $query->where('gender', '=', $gender);
                    // }
                }
            })
            ->where(function ($query) use ($start_age, $end_age) {
                if ($start_age) {
                    $query->whereBetween(DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(CURDATE(),dob)), ' . "'%Y'" . ')+0'), array($start_age, $end_age));
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereBetween(DB::raw('DATE(created_at)'), array($start_date, $end_date));
                }
            })
            ->groupBy(['platform'])
            ->get();
        $result = [];
        $active_user  = [];
        $groupedArray = [];
        $androidCount = 0;
        $iosCount = 0;
        foreach ($getUsers as $user) {
            if ($user['platform'] == "android") {
                $androidCount = $user['download_count'];
            }
            if ($user['platform'] == "ios") {
                $iosCount = $user['download_count'];
            }

            $result['download_count'] = $user['download_count'];
            $result['platform']       = $user['platform'];
            $result['created_at']     = explode(" ", $user['created_at'])[0];
            $active_user = date('Y-m', strtotime($result['created_at']));
            $groupedArray[$active_user][] = $result;
        }

        return array('android' => $androidCount, 'ios' => $iosCount, 'no_of_downloads' => $groupedArray);
    }

    /**
     * This method is used to get user settings
     */
    public function getUserSettings($user)
    {
        $data = User::with('country:id,name')->select('users.id as user_id', 'users.username as username', 'users.dob as dob', 'users.gender', 'users.country_id as country_id', 'users.city as user_city', 'users.email as email')
            ->where('users.id', $user->id)
            ->get();
        return $data ? $data->toArray() : [];
    }

    /**
     * Create user topic from defailt topics
     */
    public function createUserTopics($user, $topics)
    {
        $user->userTopics()->sync($topics);
        $createUserTopics = $user->load('userTopics');
        return $createUserTopics ? $createUserTopics : [];
    }

    /**
     * get user all topics by iddata
     */
    public function getAllUserTopicsById($id)
    {
        $data   =   UserTopic::where('user_id', $id)->select('def_topic_id')->pluck();
        return (!$data->isEmpty()) ? $data->toArray() : [];
    }

    /**
     * This toggle method is used to follow and unfollow users
     */
    public function toggleFollow($user, $otherUserId)
    {
        return $user->follows()->toggle([$otherUserId]);
    }
    /*
     * get all users
     */
    public function getUsers($type, $limit, $offset, $search)
    {
        /* get all users profiles */
        if ($type == "profile") {
            $data = User::select(
                'users.id as id',
                'users.profile_image',
                'users.thumb_image',
                'users.username',
                'users.email',
                'users.status',
                DB::raw('(Select COUNT(DISTINCT(post_rankings.post_id)) from post_rankings LEFT JOIN posts ON posts.id = post_rankings.post_id where user_id = users.id) as top_hundred_count')
            )
                ->withCount('post')
                //->withCount('post.postRanking')
                ->whereIn('users.status', [Constants::USER_STATUS_ACTIVE_INT, Constants::USER_STATUS_INACTIVE_INT, Constants::USER_STATUS_BANNED_INT])
                ->where('users.role_id', '!=', Constants::ROLE_ADMIN);
            $data = $data->where(function ($query) use ($search) {
                $columns = ['users.username', 'users.id'];
                foreach ($columns  as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
            $count = $data->count();
            $data = $data->skip($offset)
                ->take($limit)
                ->get();

            return $data ? array("data" => $data, "count" => $count) : [];
        }

        /* get pending users */
        if ($type == "registration") {
            $adminConfig = new AdminConfig();
            $getConfig   = $adminConfig->getConfig();

            $data = User::with('country:id,name')->select(
                'users.id as id',
                'users.country_id',
                'users.username',
                'users.email',
                'users.status'
            )
                ->whereIn('users.status', [Constants::USER_STATUS_ADMIN_PERMIT_REQUIRED_INT, Constants::USER_STATUS_PERMIT_PROFILE_INCOMPLETE_INT])
                ->where('users.role_id', '!=', Constants::ROLE_ADMIN);
            $data = $data->where(function ($query) use ($search) {
                $columns = ['users.username', 'users.id'];
                foreach ($columns  as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
            $count = $data->count();
            $data = $data->skip($offset)
                ->take($limit)
                ->get();

            $response = ($getConfig[0]['status'] == Constants::STATUS_FALSE) ? $data : [];

            return $data ? array("data" => $response, "count" => $count) : [];
        }
    }
    /*
     * get user by id
     */
    public function getUserDetail($id, $type)
    {
        if ($type == "all") {
            $status = [];
        } elseif ($type == "live") {
            $status = [Constants::POSTS_TYPE_ACTIVE];
        } elseif ($type == "top") {
            // $status = Constants::POSTS_TYPE_ACTIVE;
            $user   =   User::select('id', 'profile_image', 'thumb_image', 'username', 'email', 'status', DB::raw('(Select COUNT(DISTINCT(post_rankings.post_id)) from post_rankings LEFT JOIN posts ON posts.id = post_rankings.post_id where user_id = users.id) as top_hundred_count'))
                ->where('users.id', $id)->get();
            // dd($user);
            return $userPostRanking    =   $user->load(['post' => function ($query) {
                $query->whereHas('postRanking');
            }, 'post.postRanking'])->loadCount('post');
        } elseif ($type == "removed") {
            $status = [Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED];
        }

        $user = User::select('id', 'profile_image', 'thumb_image', 'username', 'email', 'status', DB::raw('(Select COUNT(DISTINCT(post_rankings.post_id)) from post_rankings LEFT JOIN posts ON posts.id = post_rankings.post_id where user_id = users.id) as top_hundred_count'))
            ->where('users.id', $id)
            ->withCount('post')
            ->get();

        $post = Post::select('file_url', 'thumb_url', 'status', 'media_type')
            ->where('user_id', $id)
            ->where(function ($query) use ($status) {
                if ($status) {
                    $query->whereIn('posts.status', $status);
                }
            })
            ->get();

        return $user ? array("user" => $user, "post" => $post) : [];
    }
    /*
    * update user status by id
    */
    public function updateUserStatus($userId, $input)
    {
        return $updateUser = User::where('id', $userId)->update($input);
    }

    /**
     * get user favourites with their created date,current,highest ranks and topics
     */
    public function getMyFavourites($user, $topic, $country, $limit, $offset)
    {
        // $topic  =   '';
        $data   =   $user->select('id')->withCount(['postReactions' => function ($query) use ($user,$topic, $country, $limit, $offset) {
            $query->whereHas('postLiked', function ($childQuery) use ($topic, $country) {
                if ($topic) {
                    $childQuery->whereIn('user_topic_id', $topic);
                }
                if ($country) {
                    $childQuery->where('country_id', $country);
                }
            })
            ->whereIn('id',[DB::raw("select max(id) from post_reactions where user_id=$user->id group by post_reactions.post_like_id")]);
        }])
            ->with(['postReactions' => function ($query) use ($user, $topic, $country, $limit, $offset) {

                $query->select('id', 'user_id', 'post_like_id', 'created_at')
                    ->whereHas('postLiked', function ($childQuery) use ($topic, $country) {
                        if ($topic) {
                            $childQuery->whereIn('user_topic_id', $topic);
                        }
                        if ($country) {
                            $childQuery->where('country_id', $country);
                        }

                    })
                    ->whereIn('id',[DB::raw("select max(id) from post_reactions where user_id=$user->id group by post_reactions.post_like_id")])
                    ->orderBy('id', 'desc')
                    ->take($limit)->skip($offset);
            }, 'postReactions.postLiked.postTopic:id,name', 'postReactions.postLiked.postRanking'])
            ->where('id', $user->id)
            ->get();
        return $data;
    }

    public function getUserPostsLive($userId, $limit, $offset)
    {
        return $userPost           =   User::find($userId)->loadCount(['post' => function ($query) use ($limit, $offset) {
            $query->whereIn('status',  [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED, Constants::POSTS_TYPE_REPORTED]);
        },])
            ->load(['post' => function ($query) use ($limit, $offset) {
                $query->whereIn('status',  [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED, Constants::POSTS_TYPE_REPORTED])->take($limit)->skip($offset);
            }, 'post.postRanking', 'post.postTopic']);
    }
    public function getUserPostsRemoved($userId, $limit, $offset)
    {
        return $userPost           =  User::find($userId)->loadCount(['post' => function ($query) {
            $query->whereIn('status',  [Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED]);
        }])
            ->load(['post' => function ($query) use ($limit, $offset) {
                $query->whereIn('status', [Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED])->take($limit)->skip($offset);
            }, 'post.postRanking', 'post.postTopic']);
    }
    public function getUserPostRanking($userId, $limit, $offset)
    {
        return $userPostRanking    =   User::find($userId)->loadCount(['post' => function ($query) {
            $query->whereHas('postRanking');
        }])
            ->load(['post' => function ($query) use ($limit, $offset) {
                $query->take($limit)->skip($offset)->whereHas('postRanking');
            }, 'post.postRanking', 'post.postTopic']);
    }

    /**
     * getUserAllPosts : get all user posts
     */
    public function getUserAllPosts($userId, $limit, $offset)
    {
        return $userPost           =   User::find($userId)->loadCount('post')->load(['post' => function ($query) use ($limit, $offset) {
            $query->take($limit)->skip($offset);
        }, 'post.postRanking', 'post.postTopic']);
    }

    /**
     * Get all users and searched user
     */
    public function getAllUsers($user, $like, $limit, $offset)
    {
        $data = User::select('users.id as id', 'users.username as username', 'users.profile_image as profile_image', 'users.thumb_image as thumb_image')
            ->where(function ($query) use ($like) {
                if ($like) {
                    $query->where('username', 'like', '%' . $like . '%');
                }
            })
            ->where('users.status', Constants::USER_STATUS_ACTIVE_INT)
            ->where('users.role_id', Constants::ROLE_USER)
            ->where('users.id', '!=', $user->id);
        return $data;
    }
}
