<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\createPost;
use App\Http\Requests\getCms;
use App\Http\Requests\togglePost;
use App\Models\AdminConfig;
use App\Models\country;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostRanking;
use App\Models\PostReaction;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class PostController extends Controller
{
    /**
     * Get Posts
     */
    public function getPosts(Request $request)
    {
        $user       =   Auth::user();
        $age        =   Carbon::parse($user->dob)->diff(Carbon::now())->y;
        $type       =   $request->header('type') ? $request->header('type') : Constants::POSTS_TYPE_LUCKY_DIP;
        $location   =   $request->header('location') == 'all' ? "" : $request->header('location');
        $topic      =   $request->header('topic');
        $topic      =   $this->getRequestedTopic($topic, $user->id);
        $time       =   $request->header('time');
        $startDate       =   $this->returnStartDate($time);
        $endDate        =   Carbon::now()->toDateString();
        $input      =   $request->all();
        $postModel       =  new Post();
        $favouritePost  =   $postModel->getFavouritePost($user->id);
        if ($request->filled('post_like_id') || $request->filled('post_dislike_id')) {
            $insertPostReaction['user_id']         = $user->id;
            $insertPostReaction['post_like_id'] = $input['post_like_id'];
            $insertPostReaction['post_dislike_id'] = $input['post_dislike_id'];
            $insertPostReaction['status']          = Constants::STATUS_TRUE;
            $insertPostReaction['created_at']      = $input['current_time'];
            $postReaction = new PostReaction();
            $addPostReachtion = $postReaction->addPostReaction($insertPostReaction);
            if (!$addPostReachtion) {
                return response()->json(['status' => '400', 'error' => array('message' => 'Error add post reaction.')], 400);
            }
            $updatePostFlag =   $postModel->updatePostScores($input['post_like_id'], $input['post_dislike_id'], $user);
            // if ($updatePostFlag == 0) {
            //     return response()->json(['status' => '400', 'error' => array('message' => 'Error add post scores.')], 400);
            // }
            $favouritePost = $postModel->getFavouritePost($user->id);
            $getPosts = ($type == Constants::POSTS_TYPE_LUCKY_DIP) ? $postModel->getInitialPosts($favouritePost->post_id, $user->id, $location, $topic, $startDate, $endDate, $age, $followPost = false) : $postModel->getInitialPosts($favouritePost->post_id, $user->id, $location, $topic, $startDate, $endDate, $age, $followPost = true);
        } else if ($request->header('alt-id') && $request->header('alt-id') != 'undefined') {
            $favPostId  =   $favouritePost ? $favouritePost->post_id : 0;
            $altPostId  =   $request->header('alt-id');
            $validPost   =   Post::find($altPostId);

            if (!$validPost) {
                return response()->json([
                    'staus' =>  400,
                    'success'   =>  false,
                    'message'    =>  "Please enter a valid alt-id"
                ], 400);
            }

            if ($favPostId != 0) {
                $getPosts = $postModel->getInitialPosts($favPostId, $user->id, $location, $topic, $startDate, $endDate, $age, $followPost = false, $altPostId);
            } else {
                $getPosts = $postModel->getInitialPosts($favPostId, $user->id, $location, $topic, $startDate, $endDate, $age, $followPost = false, $altPostId);
            }
        } else {
            $favPostId  =   $favouritePost ? $favouritePost->post_id : 0;
            $getPosts = ($type == Constants::POSTS_TYPE_LUCKY_DIP) ? $postModel->getInitialPosts($favPostId, $user->id, $location, $topic, $startDate, $endDate, $age, $followPost = false) : $postModel->getInitialPosts($favPostId, $user->id, $location, $topic, $startDate, $endDate, $age, $followPost = true);
            // $getPosts = $postModel->getInitialPosts($favPostId, $user->id, $location, $topic, $start_date, $end_date);
        }


        $this->updatePostSeenCount($favouritePost, $getPosts);
        $adminConfigModel   =   new AdminConfig();
        $adminConfig    =   $adminConfigModel->getSpecificConfig('show_ads_interval');
        $admobFlag    =   $adminConfigModel->getSpecificConfig('add_mob');
        $customAdFlag    =   $adminConfigModel->getSpecificConfig('custom_ad');
        $success = [
            'favourite_post'    =>  $favouritePost,
            'alternative_post'  => $getPosts,
            $adminConfig->rule  => $adminConfig->value,
            $admobFlag->rule    =>  $admobFlag->status,
            $customAdFlag->rule =>  $customAdFlag->status
        ];

        return response()->json([
            'staus' =>  200,
            'success'   =>  true,
            'message'    =>  "Data fetched successfully",
            'data'       =>  $success
        ]);
    }

    /**
     * Create Post
     */
    public function createPost(createPost $request)
    {
        try {
            $user   =   Auth::user();
            $topicId   =   $request->input('topic_id');
            $currentTime   =   $request->input('current_time');
            $type           =   $request->input('type');
            if ($type == 'video') {
                if ($request->hasFile('file')) {
                    $file = $request->file('file');

                    $postModel = new Post();
                    $adminConfigModel   =   new AdminConfig();

                    $postVideoPath = $postModel->uploadPostVideoFile($file);
                    if ($postVideoPath) {
                        DB::beginTransaction();
                        //add file
                        $postFileDataToInsert['user_id']            = $user->id;
                        $postFileDataToInsert['user_topic_id']      = $topicId;
                        $postFileDataToInsert['country_id']         = $user->country_id;
                        $postFileDataToInsert['title']              = '';
                        $postFileDataToInsert['score']              = $adminConfigModel->getSpecificConfig('post_uploaded_point')['value'];
                        $postFileDataToInsert['file_url']           = $postVideoPath['video_url'];
                        $postFileDataToInsert['thumb_url']          = $postVideoPath['video_thumb'];
                        $postFileDataToInsert['media_type']         = Constants::MEDIA_TYPE_VIDEO;
                        $postFileDataToInsert['status']             = Constants::STATUS_ACTIVE;
                        $postFileDataToInsert['created_at']         = $currentTime;
                        $postFileDataToInsert['updated_at']         = $currentTime;

                        $postModel  =   new Post();
                        $Createdpost   = $postModel->createPost($postFileDataToInsert);
                        if ($Createdpost) {
                            DB::commit();
                            return response()->json(['status'  => '200', 'success' => array('message' => 'Post Created.')], 200);
                        } else {
                            return response()->json([
                                'status'    =>  500,
                                'success'   =>  false,
                                'error'     =>  [
                                    'message'   =>  'Server Error'
                                ]
                            ], 500);
                        }
                    } else {
                        return response()->json([
                            'status'    =>  400,
                            'success'    =>  false,
                            'error'   =>  'Please upload video less than or equal 15 seconds and 50mb'
                        ], 400);
                    }
                } else {
                    return response()->json(['status' => '400', 'error' => array('message' => 'Choose Video File.')], 400);
                }
            } else {
                //storing post image
                if ($request->hasFile('file')) {
                    $image          = $request->file('file');
                    $extension      = $image->getClientOriginalExtension();
                    $imageName      = $image->getClientOriginalName();
                    $imageName      = str_replace(' ', '_', $imageName);
                    $fileNameWithoutExt = pathinfo($imageName, PATHINFO_FILENAME);
                    $destinationPath = base_path('public/user_images/user_' . $user->id . '');
                    if (!file_exists($destinationPath)) {
                        //create folder
                        mkdir($destinationPath, 0777, true);
                    }
                    $time           = time();
                    $imageUrl       = $fileNameWithoutExt . '_' . $time . '.' . $extension;
                    $image->move($destinationPath, $imageUrl);

                    //generating thumbnail
                    $image          = ImageManagerStatic::make($destinationPath . '/' . $imageUrl)->resize('550', '340');
                    $image->orientate();
                    $thumbImageUrl  = '/thumb_' . $fileNameWithoutExt . '_' . $time . '-' . '550x340' . '.' . $extension;
                    $image->save($destinationPath . $thumbImageUrl);

                    $urlImage = $destinationPath . '/' . $imageUrl;
                    $urlThumb = $destinationPath . $thumbImageUrl;

                    //s3 Configurations
                    $imagePath = 'user_images/user_' . $user->id . '/' . $imageUrl;
                    $thumbPath = 'user_images/user_' . $user->id . '' . $thumbImageUrl;
                    if (env('APP_ENV') == "production") {

                        $thumbS3Storage = Storage::disk('s3')->put($thumbPath, \fopen($urlThumb, 'r+'));
                        $imageS3Storage = Storage::disk('s3')->put($imagePath, file_get_contents($urlImage));

                        //if stored to s3 delete from local directory
                        if ($thumbS3Storage) {
                            unlink($urlThumb);
                            if ($imageS3Storage) {
                                unlink($urlImage);
                            }
                        }

                        $temp[] = array(
                            'full_path' => env('AWS_URL') . $imagePath,
                            'thumb_path' => env('AWS_URL') . $thumbPath
                        );
                    } else {
                        //without s3 local storage
                        $baseUrl        = (env('APP_ENV') == 'local') ? 'http://127.0.0.1:8001' : env('APP_STAGGING_URL');

                        $temp[] = array(
                            'full_path' => $baseUrl . '/' . $imagePath,
                            'thumb_path' => $baseUrl . '/' . $thumbPath
                        );
                    }
                    DB::beginTransaction();
                    $adminConfigModel   =   new AdminConfig();
                    $postFileDataToInsert['user_id']            = $user->id;
                    $postFileDataToInsert['user_topic_id']      = $topicId;
                    $postFileDataToInsert['country_id']         = $user->country_id;
                    $postFileDataToInsert['title']              = '';
                    $postFileDataToInsert['score']              = $adminConfigModel->getSpecificConfig('post_uploaded_point')['value'];
                    $postFileDataToInsert['file_url']           = $imagePath;
                    $postFileDataToInsert['thumb_url']          = $thumbPath;
                    $postFileDataToInsert['media_type']         = Constants::MEDIA_TYPE_IMAGE;
                    $postFileDataToInsert['status']             = Constants::STATUS_ACTIVE;
                    $postFileDataToInsert['created_at']         = $currentTime;
                    $postFileDataToInsert['updated_at']         = $currentTime;

                    $postModel  =   new Post();
                    $Createdpost   = $postModel->createPost($postFileDataToInsert);
                    if ($Createdpost) {
                        DB::commit();
                        return response()->json(['status'  => '200', 'success' => array('message' => 'Post Created.')], 200);
                    } else {
                        return response()->json([
                            'status'    =>  500,
                            'success'   =>  false,
                            'error'     =>  [
                                'message'   =>  'Server Error'
                            ]
                        ], 500);
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   =>  $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * update post seen count
     */
    public function updatePostSeenCount($favouritePost, $getPosts)
    {
        $postModel                      =   new post();
        if ($favouritePost) {
            $favouritePost              =   Post::find($favouritePost->post_id);
            $seenCount                  =   $favouritePost->seen_count;
            $seenCount++;
            $data['seen_count']         =   $seenCount;
            $updateFavouriteSeenCount   =   $postModel->updatePost($favouritePost->id, $data);
        }
        if ($getPosts) {
            foreach ($getPosts as $post) {
                if (is_array($post)) {
                    $post = (object) $post;
                }
                // dd($post->post_id);
                $seenCount = 0;
                $altPost                =   Post::find($post->post_id);
                $seenCount              =   $altPost->seen_count;
                $seenCount++;
                $data['seen_count']     =   $seenCount;
                $updateAltSeenCount     =   $postModel->updatePost($altPost->id, $data);
            }
        }
    }

    /**
     * return post start date using time filter
     */
    public function returnStartDate($time)
    {
        $startDate = null;
        switch ($time) {
            case 'all':
                $startDate  = null;
                break;
            case 'last_month':
                // $startDate = date('Y-m-d', strtotime('-1 month'));
                $startDate  =   Carbon::now()->subMonth()->toDateString();
                break;
            case 'last_year':
                // $startDate = date('Y-m-d', strtotime('-1 year'));
                $startDate  =   Carbon::now()->subYear()->toDateString();
                break;
            default:
                $startDate = null;
                break;
        }
        return $startDate;
    }

    /**
     * return desired topics
     */
    public function getRequestedTopic($topic, $userId)
    {
        switch ($topic) {
            case 'all':
                $returnTopics = [];
                break;
            case 'default':
                $arrayOfMyTopics = [];
                $returnTopics   = Topic::with(['topicUsers' => function ($query) use ($userId) {
                    $query->whereRaw('user_id = ' . $userId);
                }])->get();
                foreach ($returnTopics as $topic) {
                    $check  = isset($topic->topicUsers[0]) ? $topic->topicUsers[0]->id  : null;
                    if ($check) {
                        array_push($arrayOfMyTopics, $topic->id);
                    }
                }
                $returnTopics = $arrayOfMyTopics;
                break;
            default:
                $returnTopics   =   (array) $topic;
                break;
        }
        return $returnTopics;
    }

    /**
     * return post details
     */
    public function getPostDetails(Post $post)
    {
        $postModel  =   new post();
        $authUser   =   Auth::user();
        $post = $post->load(['user', 'postTopic:id,name', 'postRanking:post_id,current_rank,highest_rank']);
        if ($post) {
            $postClicks = $post->click;
            $postClicks++;
            $data['click']  =  $postClicks;
            $updatePostClick    =   $postModel->updatePost($post->id, $data);
            $post['is_follow']  =   $post->user->follower->contains($authUser->id);
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Data fetch successfully',
                'data'      =>  $post
            ]);
        }
        return response()->json([
            'status'    =>  400,
            'success'   =>  false,
            'error'     =>  "Error fetching data"
        ]);
    }

    /**
     * Get top 100 posts and mantain ranking
     * User Stories:
     * == As a user, I should be able to apply filters on the posts available on the Top 100 Post.
     * Top 100 Posts,User with filter
     * User Stories :
     * top 100
     *  Topic & time will be the two filters available
     *  The Top 100 screen will display all the best posts based on the point and ranking system.
     *  Landing on the screen will filter the results based on the user’s topic preferences.
     *  Topics in the filter will be “My Topics” by default.
     *  In the time dropdown, there will be an option for the user to select to All Time, Last Year, Last Month. By default it will be "All Time" filter, by selecting filter to 'Last Year', all the Top 100 posts from last year appears on this screen and  by selecting filter to 'Last Month', all the Top 100 posts from last month appears on this screen.
     *  Users will have the option to filter the results from the filters defined. (Location, Topics, time)
     *  Landing on the screen will show 2 tabs:
     *  Posts
     *  Accounts
     *  == As a user, I should be able to view post that are top ranked according to the applied filters.
     *  Posts that reached minimum of (set in the config/ admin panel) in their life time once within the topics. If the ranking falls below 100 after this, they will still be part of the top 100 leaderboard for life.
     *  It will be showing the top 100 posts from all the topics combined as per user’s preference.
     *  Changing any filter will recalculate the ranks for the posts based on the filters applied.
     *  Acceptance Criteria
     *  Top 100 posts will display a list of top 100 posts.
     *  Tapping on a post will take user to detail view and all information about the post will be available
     *  The post should qualify the top 100 posts if it achieves all the conditions and criteria.
     *  Posts that reached the top 100 will never be expired.
     *  The top 100 will be a global list.
     *  The ranks of all posts that qualifies in top 100 should be maintained.
     *  Accounts should display the list of all top 100 users.
     *  Users can qualify for the top 100 users based on the number of posts that reaches the top 100.
     *  By tapping on the post details users will have the provision to ‘share’ and ‘report’ and ‘compare with favorite’ by clicking on the button and other post details such highest and current ranks and topic details and date.
     */
    public function getTopPosts(Request $request)
    {
        $user       =   Auth::user();
        $topic      =   $request->input('topic');
        $time       =   $request->input('time');
        $limit      =   $request->input('limit')  ? $request->input('limit') : 10;
        $offset     =   $request->input('offset') ? $request->input('offset') : 0;
        $filter     =   $request->input('type');

        $postModel              =   new Post();
        $postRankingModel       =   new PostRanking();
        $topic                  =   $this->getRequestedTopic($topic, $user->id);
        $startDate              =   $this->returnStartDate($time);
        $endDate                =   Carbon::now()->toDateString();
        $data                  =    ($filter == 'posts') ? $postModel->getTopPosts($user, $topic, $startDate, $endDate) : $postRankingModel->getTopUsers($user, $topic, $startDate, $endDate);
        $dataWithoutCount       =   $data;
        $count                  =   ($filter == 'posts') ? $data->count() : count($data->get());

        if ($offset >= 90 && $limit > 10) {
            return response()->json([
                'status'    =>  200,
                'success'   =>  false,
                'message'   =>  'Data Fetched Successfully',
                'count'     =>  $count,
                'data'      =>  []
            ]);
        }

        $postWithoutCount       =   $dataWithoutCount->take($limit)->skip($offset)->get();
        // $updatedPost            =   $this->updatePostRankings($user, $postWithoutCount);


        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data Fetched Successfully',
            'count'     =>  $count,
            'data'      =>  $postWithoutCount
        ]);
    }

    /**
     * update post rankings
     */
    public function updateRankings()
    {
        $notificationModel  =   new Notification();
        $startDate          =   Carbon::now(new DateTimeZone('UTC'))->startOfMonth()->format('Y-m-d');
        $endDate            =   Carbon::now(new DateTimeZone('UTC'))->addDay()->format('Y-m-d');
        $currentTime        =   Carbon::now(new DateTimeZone('UTC'))->format('Y-m-d h:m:s');
        $adminSettingModel  =   new AdminConfig();
        $topics             =   Topic::select('id')->get();
        $postMinimumReach   =   $adminSettingModel->getSpecificConfig(Constants::RULE_MINIMUM_REACH_POINTS)['value'];
        foreach ($topics as $topic) {

            $posts              =   Post::select('id', 'user_id')->with('user')->where('user_topic_id', $topic->id)
                ->where('posts.score', '>=', $postMinimumReach)
                ->whereIn('posts.status', [Constants::POSTS_TYPE_ACTIVE, Constants::POSTS_TYPE_RELEASED])
                ->whereBetween('posts.updated_at', [$startDate, $endDate])
                ->orderBy('posts.score', 'desc')
                ->limit(100)
                ->get();

            $postRankingModel   =   new PostRanking();
            $i = 1;
            foreach ($posts as $post) {
                $sendNotificationFlag = false;
                $postRanking                        =   PostRanking::where('post_id', $post->id)->first();
                if ($postRanking) {
                    $highestRank                    =   ($i < $postRanking->highest_rank) ? $i :  $postRanking->highest_rank;
                    $rankData['current_rank']           =   $i;
                    $rankData['highest_rank']           =   $highestRank;
                    $rankData['updated_at']             =   $currentTime;
                    $postRankingModel->updatePostRanking($post->id, $rankData);
                } else {
                    $rankData['current_rank']           =   $i;
                    $rankData['highest_rank']           =   $i;
                    $rankData['updated_at']             =   $currentTime;
                    $rankData['created_at']             =   $currentTime;
                    $postRankingModel->updatePostRanking($post->id, $rankData);
                    $sendNotificationFlag = true;
                }

                //check if the post reached in ranking for the first time flag above in else condition becomes true when ranking generates for the first time
                if ($sendNotificationFlag) {
                    //sending notification to the user of the post
                    $deviceToken    =   $post->user->device_token;
                    $isNotify       =    $post->user->is_notify;
                    $message        =   'Your post has reached top 100 rankings';
                    $data           =   array('post_id' => $post->id);
                    $notificationData['user_id']            =   $post->user->id;
                    $notificationData['other_user_id']      =   $post->user->id;
                    $notificationData['post_id']            =   $post->id;
                    $notificationData['body']               =   $message;
                    $notificationData['status']             =   Constants::NOTIFICATION_STATE_TYPE_UNREAD;
                    $notificationData['type']               =   Constants::NOTIFICATION_TYPE_POST_REACHED_TOP;
                    $notificationData['created_at']         =   $currentTime;
                    $notificationData['updated_at']         =   $currentTime;

                    $notificationModel->createNotification($notificationData);
                    if ($deviceToken && $isNotify == Constants::POST_STATUS_ACTIVE) {
                        $notificationModel->sendNotification($deviceToken, $message, $data);
                    }
                }
                $i++;
            }
        }

        return response()->json([
            'status'        =>  200,
            'success'       =>  true,
            'message'       =>  'Post ranking updated'
        ]);
    }

    /**
     *
     * Get CMS
     *
     * It will be a simple operating screen where admin will be able to view all the posts that had either been reported or disable (suspended/removed).
     * Admin can simply view all flagged post or filter them from the tabs
     *
     *
     * Auto Disabling Algorithm
     *      There will be 2 settings in config screen for content/reported posts:
     *
     *      Percentage set against reported vs views & when this will be triggered based on the number of reported or flagged.
     *      When reported, 1st it will check from config the limit of reports (number of reports percentage view). Then it will check the number of views. If the percentage of views vs reported meets the number, the post will be blocked.
     *
     *      Once a post is suspended based on the above conditions, it will be subject to review by the admin.
     *      As soon as admin allows a reported/suspended post to be visible again, the above algorithm criteria will be disabled for the particular post.
     *
     *
     * Enable/Disable
     *      Admin will be able to enable the disabled posts (posts that are disabled based on the parameters set in the config items). Admin will be able to disable the reported post. Once a post which become disable (based on the parameters set in the config items) enabled by admin can not be disabled from the parameters set in the config items. Admin can be disable them manually. However, if the post is again reported, it will still show in the content management section for reported post.
     *
     *
     *
     */

    public function getCms(getCms $request)
    {
        $offset =   $request->input('offset') ? $request->input('offset') : 0;
        $limit  =   $request->input('limit') ? $request->input('limit') : 10;
        $sort   =   $request->input('sort')   ? $request->input('sort')   : 'asc';
        $filter =   $request->input('filter') ? $request->input('filter') :   '';
        $like   =   $request->input('like');

        $postModel  =   new Post();
        $filter     =   $this->getPostIntegerStatus($filter);
        $data       =   $postModel->getCmsRecords($offset, $limit, $sort, $filter, $like);

        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data fetched successfully',
            'count'     =>  $data['count'],
            'data'      =>  $data['data']
        ]);
    }

    /*
    * This method use for get post status integer.
    */
    public function getPostIntegerStatus($value)
    {
        $status = '';
        switch ($value) {
            case 'active':
                $status =  [1];
                break;
            case 'hidden':
                $status = [2];
                break;
            case 'reported':
                $status = [3];
                break;
            case 'removed':
                $status = [4];
                break;
            case 'suspended':
                $status = [5];
                break;
            case 'released':
                $status = [6];
                break;
            default:
                $status = [3, 4, 5, 6];
                break;
        }
        return $status;
    }

    /**
     * This method is used to enable / disable post
     */
    public function togglePost(Request $request)
    {
        $targetPostId   =   $request->input('post_id');
        $currentTime    =   $request->input('current_time');
        $postModel      =   new Post();
        $PostById       =   Post::find($targetPostId)->load('user');
        if (!$PostById) {
            return response()->json(['status' => '400', 'error' => 'Please enter a valid id'], 400);
        }
        if ($PostById->status == 'reported' || $PostById->status == 'released') {
            $changed_status = Constants::POSTS_TYPE_REMOVED;
            if (!filled($request->input('reason'))) {
                return response()->json(['status' => '400', 'error' => 'Required fields cannot be left empty'], 400);
            }
            $data['reason'] =   $request->input('reason');
            $notifyMessage  =   'Disabled';
            $notifyType     =   Constants::NOTIFICATION_TYPE_ADMIN_SUSPENDED;
        }
        // elseif ($PostById->status == 'removed') {
        //     $changed_status = Constants::POSTS_TYPE_ACTIVE;
        //     $data['reason'] = "";
        //     $notifyMessage  =   'Active';
        //     $notifyType     =   Constants::NOTIFICATION_TYPE_POST_ACTIVE;
        // }
        elseif ($PostById->status == 'suspended' || $PostById->status == 'removed') {
            $changed_status = Constants::POSTS_TYPE_RELEASED;
            $data['reason'] = "";
            $notifyMessage  =   'Released';
            $notifyType     =   Constants::NOTIFICATION_TYPE_POST_RELEASED;
        }
        $data['status'] =   $changed_status;
        $data['updated_at'] =   $currentTime;
        $updatePost     =   $postModel->updatePost($targetPostId, $data);
        if ($updatePost) {
            $deviceToken    =    $PostById->user->device_token;
            $isNotify       =    $PostById->user->is_notify;
            $notificationModel       =   new Notification();
            $fcmMessage         =   'Your post has been ' . $notifyMessage . ' by admin';
            $fcmData            =   array("post_id" => $targetPostId);
            $notificationData['user_id']            =   $PostById->user->id;
            $notificationData['other_user_id']      =   $PostById->user->id;

            $notificationData['post_id']            =   $targetPostId;
            $notificationData['body']               =   $fcmMessage;
            $notificationData['status']             =   Constants::NOTIFICATION_STATE_TYPE_UNREAD;
            $notificationData['type']               =   $notifyType;
            $notificationData['created_at']         =   $currentTime;
            $notificationData['updated_at']         =   $currentTime;

            $notificationModel->createNotification($notificationData);
            if ($deviceToken && $isNotify == Constants::POST_STATUS_ACTIVE) {
                $notifyResponse=$notificationModel->sendNotification(
                    $deviceToken,
                    $fcmMessage,
                    $fcmData
                );
            }

            return response()->json([
                'status'    =>  200,
                'success'   =>  'true',
                'firebase_response'    =>   $notifyResponse,
                'message'   =>  'Post updated successfuly'
            ]);
        }

        return response()->json([
            'status'    =>  400,
            'success'   =>  'false',
            'error'     =>  'Post update failed'
        ]);
    }

    /**
     * Auto disable post
     * Auto Disabling Algorithm
     *   There will be 2 settings in config screen for content/reported posts:
     *
     *   Percentage set against reported vs views & when this will be triggered based on the number of reported or flagged.
     *   When reported, 1st it will check from config the limit of reports (number of reports percentage view). Then it will check the number of views. If the percentage of views vs reported meets the number, the post will be blocked.
     *
     *   Once a post is suspended based on the above conditions, it will be subject to review by the admin.
     *   As soon as admin allows a reported/suspended post to be visible again, the above algorithm criteria will be disabled for the particular post.
     */
    public function autoDisable()
    {
        $adminConfigModel   =   new AdminConfig();
        $notificationModel  =   new Notification();
        $currentTime        =   Carbon::now(new DateTimeZone('UTC'))->format('Y-m-d h:m:s');
        $posts              =   Post::select('id as post_id', 'seen_count as views', 'user_id')->withCount('reports')->with('user')->get();
        $percentage         =   $adminConfigModel->getSpecificConfig('percentage_of_views')['value'];
        $minThreshold       =   $adminConfigModel->getSpecificConfig('min_number_of_views')['value'];
        $postModel          =   new Post();
        foreach ($posts as $post) {
            if ($post->views >= $minThreshold) {
                $median             =   ($post->reports_count / $post->views) * 100;
                if ($median >= $percentage) {
                    $data['status']     =   Constants::POSTS_TYPE_SUSPENDED;
                    $data['reason']     =   'Post has been auto-disabled';
                    $data['updated_at'] =   $currentTime;
                    $updatePost     =   $postModel->updatePost($post->post_id, $data);

                    //sending notification to the user of the post
                    $deviceToken    =   $post->user->device_token;
                    $isNotify       =    $post->user->is_notify;
                    $message        =   'Your post has been suspended';
                    $data           =   array('post_id' => $post->id);
                    $notificationData['user_id']            =   $post->user->id;
                    $notificationData['other_user_id']      =   $post->user->id;
                    $notificationData['post_id']            =   $post->post_id;
                    $notificationData['body']               =   $message;
                    $notificationData['status']             =   Constants::NOTIFICATION_STATE_TYPE_UNREAD;
                    $notificationData['type']               =   Constants::NOTIFICATION_TYPE_AUTO_SUSPENDED;
                    $notificationData['created_at']         =   $currentTime;
                    $notificationData['updated_at']         =   $currentTime;

                    $notificationModel->createNotification($notificationData);
                    if ($deviceToken && $isNotify == Constants::POST_STATUS_ACTIVE) {
                        $notificationModel->sendNotification($deviceToken, $message, $data);
                    }
                }
            }
        }
        // if()
        // return response()->json([
        //     'status'    =>  200,
        //     'success'   =>  true,
        //     'message'   =>  'Data Fetched Successfully',
        //     'data'      =>  $posts
        // ]);
    }

    /**
     * hide older post which meets the criteria gets deleted after min threshold set on admin panel
     */
    public function hideOlderPosts()
    {
        $currentTime        =   Carbon::now(new DateTimeZone('UTC'))->format('Y-m-d h:m:s');
        $adminConfigModel   =   new AdminConfig();
        $age_post_days_old  =   $adminConfigModel->getSpecificConfig('post_retention_period')['value'];
        $records            =   Post::whereDoesntHave('postRanking')->where(DB::raw('DATEDIFF(CURDATE(),created_at)'), '>=', $age_post_days_old)->where('status', '!=', Constants::POSTS_TYPE_HIDDEN)->update([
            'status'        =>  Constants::POSTS_TYPE_HIDDEN,
            'file_url'      =>  'expired_media/expired_post.png',
            'thumb_url'     =>  'expired_media/expired_post.png',
            'updated_at'    =>  $currentTime
        ]);
        // return response()->json([
        //     'status'    =>  200,
        //     'success'   =>  true,
        //     'message'   =>  'Data Fetched Successfully',
        //     'data'      =>  $records
        // ]);
    }
}
