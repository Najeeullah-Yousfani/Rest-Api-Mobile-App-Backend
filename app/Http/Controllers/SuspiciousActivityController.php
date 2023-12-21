<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\SuspiciousActivity;
use App\Models\SuspiciousTopic;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuspiciousActivityController extends Controller
{
    //
    /**
     *
     *   As an admin, I should be able to view Suspicious Activity
     */
    public function getSuspiciousActivities()
    {
        $data['suspicious_favourites']      =   SuspiciousActivity::where('activity_type',Constants::SUSPICIOUS_ACTIVITY_INT_FAVOURITES)->with(['post:id,thumb_url','user:id,username'])->get();
        $data['suspicious_repletion']       =   SuspiciousActivity::where('activity_type',Constants::SUSPICIOUS_ACTIVITY_INT_REPLETION)->with(['post:id,thumb_url','user:id,username'])->get();
        $data['max_reported_content']       =   SuspiciousActivity::where('activity_type',Constants::SUSPICIOUS_ACTIVITY_INT_MAX_REPORTED)->with(['post:id,thumb_url','user:id,username'])->get();
        $data['suspicious_topic']           =   SuspiciousTopic::with('topic:id,name')->get();

        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data Fetched Successfully',
            'data'      =>  $data
        ]);

    }

    /**
     *
     *   After every 24-hour, the data will be refreshed and latest 24-hour record will be displayed for next 24-hours.
     *
     *   Suspicious ‘favourites’ – where a very low scoping post is selected over a very high scoring one, If point difference more than 30 points and low score post selected 10 or 15 times (these number will be refined), raise the flag above for the said posts.
     *
     *   Suspicious repletion of a favourite – may suggest that it is being like to ‘inflate’ the score /climb the rankings, If post gets consecutive likes of more than 50 by a single user (this number will be revised), raise the flag above for the said posts.
     *
     *   User accounts with Max reported content - Accounts that appear to be posting a lot of content that gets reported – a table / list of the profiles with the highest count of reported posts
     *
     *  Posts by Topic per day / per week / per month - This will show all the number of posts by topic for day, week and month – if any topic is not getting enough new content / posts - if <500, it could impact the rankings
     */
    public function addSuspiciousActivities()
    {

        $suspiciousActivityModel    =   new SuspiciousActivity();
        $suspiciousActivityModel->refreshRecords();
        $currentTime                =   Carbon::now(new DateTimeZone('UTC'))->format('Y-m-d h:m:s');

        $this->checkRepletionFavourites($suspiciousActivityModel, $currentTime);
        $this->checkSuspiciousFavourites($suspiciousActivityModel, $currentTime);
        $this->checkMaxReportedContent($suspiciousActivityModel, $currentTime);
        SuspiciousTopicController::checkPostByTopic($currentTime);
        // $response =  $this->checkMaxReportedContent($suspiciousActivityModel, $currentTime);
        // return response()->json([
        //     'status'    =>  200,
        //     'success'   =>  true,
        //     'message'   =>  'Data fetched successfully',
        //     'data'      =>  $response
        // ]);
    }

    //this methods find repletion post and add them to suspicious activity records
    public function checkSuspiciousFavourites($suspiciousActivityModel, $currentTime)
    {
        // $suspiciousFavourites = PostReaction::with(['postLiked', 'postDisliked'])->get();
        $suspiciousFavourites   = PostReaction::with('postLiked')->select('post_like_id','post_dislike_id',
        DB::raw('SUM(CASE WHEN ((Select score from posts where id = post_dislike_id) - (Select score from posts where id = post_like_id)) > 50
            THEN 1
            ELSE 0
        END) AS count'))->groupBy('post_like_id')->get();
        foreach ($suspiciousFavourites as $activity) {
                if ($activity->count >= 10) {
                    $data['post_id']            =   $activity->postLiked->id;
                    $data['user_id']            =   $activity->postLiked->user_id;
                    $data['poster_id']          =   $activity->postLiked->id;
                    $data['difference']         =   $activity->postLiked->score;
                    $data['activity_type']      =   Constants::SUSPICIOUS_ACTIVITY_INT_FAVOURITES;
                    $data['status']             =   Constants::POSTS_TYPE_ACTIVE;
                    $data['created_at']         =   $currentTime;
                    $addedRecord                =   $suspiciousActivityModel->createRecord($data);
                }
            }
    }

    //this methods find suspicious activities and add them to the suspicious records
    public function checkRepletionFavourites($suspiciousActivityModel, $currentTime)
    {
        $dataWithRepletion = PostReaction::select(DB::raw('COUNT(*) AS repletion_count'), 'user_id', 'post_like_id')->groupBy(['user_id', 'post_like_id'])->get();
        foreach ($dataWithRepletion as $activity) {
            if ($activity->repletion_count > 50) {
                $data['post_id']            =   $activity->post_like_id;
                $data['user_id']            =   $activity->user_id;
                $data['poster_id']          =   $activity->post_like_id;
                $data['difference']         =   $activity->repletion_count;
                $data['activity_type']      =   Constants::SUSPICIOUS_ACTIVITY_INT_REPLETION;
                $data['status']             =   Constants::POSTS_TYPE_ACTIVE;
                $data['created_at']         =   $currentTime;
                $addedRecord                =   $suspiciousActivityModel->createRecord($data);
            }
        }
    }

    //this method finds max reported content and them to the suspicious activity records
    public function checkMaxReportedContent($suspiciousActivityModel, $currentTime)
    {
        $suspiciousActivity =   User::where('users.role_id', '!=', Constants::ROLE_ADMIN)
            // ->where('users.id',2)
            ->with(
                'post',
                function ($query) {
                    $query->withCount('reports');
                }
            )
            ->withCount(['post'=>function($query)
            {
                $query->whereHas('reports');
            }])
            ->get();
            foreach ($suspiciousActivity as $user) {
            foreach ($user->post as $post) {
                //  no specified threshold till now
                if ($post->reports_count >=1) {
                    $data['post_id']            =   $post->id;
                    $data['user_id']            =   $post->user_id;
                    $data['poster_id']          =   $post->id;
                    $data['difference']         =   $user->post_count;
                    $data['no_of_reports']      =   $post->reports_count;
                    $data['activity_type']      =   Constants::SUSPICIOUS_ACTIVITY_INT_MAX_REPORTED;
                    $data['status']             =   Constants::POSTS_TYPE_ACTIVE;
                    $data['created_at']         =   $currentTime;
                    $addedRecord                =   $suspiciousActivityModel->createRecord($data);
                }
            }
        }
        // return $suspiciousActivity;
    }


}
