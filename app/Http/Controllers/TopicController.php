<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\createTopic;
use App\Http\Requests\updateTopic;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\XmlConfiguration\Constant;

class TopicController extends Controller
{


    /**
     * Create topics
     *   The topic management will be a simple operation for admin where admin will be able to create new topics and enable them.
     *   Admin will have the option to create new topics and can edit them.
     *   Once a topic is created, it cannot be deleted.
     *   While creating topic 2 options will be required:
     *           a) Topic Name – Text Field – 25 characters
     *           b) 18+ Topic – toggle – If enabled with topic, the post from this topic will not be available for age less than 18 years
     */
    public function createTopic(createTopic $request)
    {
        try {
            $topicName      =   $request->input('name');
            $isAgeLimit     =   $request->input('is_age_limit') == Constants::STATUS_TRUE_STR ? Constants::STATUS_TRUE : Constants::STATUS_FALSE;
            $status         =   Constants::STATUS_ACTIVE;
            $currentTime    =   $request->input('current_time');

            $data['name']           =   $topicName;
            $data['status']         =   $status;
            $data['is_age_limit']   =   $isAgeLimit;
            $data['created_at']     =   $currentTime;

            $topicModel =   new Topic();
            DB::beginTransaction();
            $newTopic   =   $topicModel->createTopic($data);

            if (!$newTopic) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Error creating topic'
                ], 400);
            }
            DB::commit();
            return response()->json([
                "status"    =>  200,
                'success'   =>  true,
                'message'   =>  "Topic Created Successfuly"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  true,
                'message'   =>  $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all default topics
     */
    public function getAllTopics()
    {
        try {
            $user   =   Auth::user();
            $age        =   Carbon::parse($user->dob)->diff(Carbon::now())->y;
            $colors = ['#F56258', '#F5BA3C', '#F15628', '#3EB495'];
            $topics = Topic::where('status', Constants::STATUS_ACTIVE)->where(function ($query) use ($age) {
                if ($age <= 18) {
                    $query->where('is_age_limit', Constants::STATUS_FALSE);
                }
            })->orderBy('name','asc')->get();

            $topics = $topics->toArray();
            $index = 0;
            $topicWithColor = [];
            foreach ($topics as $topic) {
                $colorCode['color']  = $colors[$index];
                array_push($topicWithColor, array_merge($topic, $colorCode));
                $index++;
                if ($index == 3) {
                    $index = 0;
                }
            }
            $topicWithColor = $topicWithColor ? $topicWithColor : array();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Data fetch successfully',
                'data'      =>  $topicWithColor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  true,
                'message'   =>  $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all the topics
     *   Landing on the screen will give a list view of all topics that had already been created and activated.
     *   Admin can search the topic by the topic name
     *   Admin enable/disable the age restriction of 18+
     *   Admin can enable/disable any topic by tapping on the icon against the name of the topic, posts created under that topic will not be shown to the users if it has been disabled by the admin.
     */
    public function getTopics(Request $request)
    {
        try {

            $like       =   $request->input('like');
            $offset     =   $request->input('limit') ? $request->input('offset') : 0;
            $limit      =   $request->input('limit') ? $request->input('limit')  : 10;

            $topics     =   Topic::where(function ($query) use ($like) {
                if ($like) {
                    $query->where('name', 'like', '%' . $like . '%');
                }
            })->orderBy('name','asc');

            $count      =   $topics;
            $count      =   $count->count();
            $data       =   $topics->take($limit)->skip($offset)->get();

            return response()->json([
                "status"    =>  200,
                "success"   =>  true,
                'message'   =>  "Data Fetched Successfuly",
                'count'     =>  $count,
                'data'      =>  $data
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'    =>  500,
                'success'   =>  true,
                'message'   =>  $e->getMessage()
            ], 500);
        }
    }

    /**
     *   Admin enable/disable the age restriction of 18+
     *   Admin can enable/disable any topic by tapping on the icon against the name of the topic, posts created under that topic will not be shown to the users if it has been disabled by the admin.
     */
    public function updateTopic(Topic $topic, updateTopic $request)
    {
        $isAgeLimit =   $request->input('is_age_limit') == Constants::STATUS_TRUE_STR ? Constants::STATUS_TRUE : Constants::STATUS_FALSE;
        $status     =   $request->input('status') == Constants::POST_STATUS_ACTIVE ? Constants::STATUS_ACTIVE : Constants::STATUS_IN_ACTIVE;
        $currentTime    =   $request->input('current_time');

        $data['is_age_limit']   =   $isAgeLimit;
        $data['status']         =   $status;
        $data['updated_at']     =   $currentTime;

        $topicModel =   new Topic();
        $updatedData    =   $topicModel->updateTopic($topic, $data);
        if (!$updatedData) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Error updating user'
            ], 400);
        }

        return response()->json([
            "status"    =>  200,
            'success'   =>  true,
            'message'   =>  "Topic Updated Successfuly"
        ]);
    }
}
