<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\userDetails;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Requests\userSignUpRequest;
use App\Http\Requests\userVerification;
use App\Models\AccessToken;
use App\Models\AdminConfig;
use App\Models\PostWeight;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
// use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPUnit\Framework\fileExists;

class AdminSettingController extends Controller
{
    /**
    * "All configuration and settings should be applied across all required domains of the application. 
    * Following are the parameters:
    * Allow auto login – To allow auto registration and login of users. Disabling this will mean that users who register themselves will have to wait unless admin approves and enables their account manually from admin panel from the user management section.
    * Minimum points to reach Top 100 – This will be the baseline point for any post to reach the top 100 ranking.
    * Admob – Enable / Disable – For mobile apps, whenever the app is launched, app will check the backend to see if the Admob flag is enabled or disabled. If disabled, mobile app will not contact the Admob service. If enabled, mobile app will request Admob service. 
    * Custom Ads – Enable / Disable – For – For mobile apps, whenever the app is launched, app will check the backend to see if the custom ads flag is enabled or disabled. If disabled, mobile app will not request backend for ads. If turned on, mobile app will receive custom ads. 
    * Show ads interval – A free text field where admin will put the number of posts after which the ads will be displayed.
    * Alternative post weights – All alternative posts weights can be set as mentioned below:
    * Score within 10
    * Score ≤ 50
    * Score ≤ 40
    * Score ≤ 30
    * Score ≤ 20
    * Post ≤ Y days old
    * Post > X days old

    * Reported post block parameters – There will be 2 settings: Percentage set against reported vs views & when this will be triggered based on the number of reported or flagged.
    * % Of views that result in post being 'reported'
    * Minimum number of views, before a post is blocked

    * Post ranking – Basic weights can be updated for calculating the points of posts. The following parameters will be configurable from config:
    * Follower
    * Repeat limit
    * Repeat weight
    * New <= Y days
    * Age 7-28 days
    * Age 29-100 days
    * Age > 100 days

    * Post retention period – How long the post (if not ranked in top 100) will be retained or after how much time the post will be expired.
    * Posts uploaded point – When 1st time post is uploaded, the default points can be updated.
    * New post days - No. of days a post is count as new
    * Validation: 
    * Text field - numbers and '.' maximum 5 characters"
    */
    public function updateConfig(Request $request)
    {
        // $user       = Auth::user();
        // $userId     = $user->id;
        $validator = Validator::make($request->all(), [
            'users'                          => 'required',
            'posts'                          => 'required',
            'post_ranking'                   => 'required',
            'reported_post_block_parameters' => 'required',
            'alternative_post_weights'       => 'required',
            'current_time'                   => 'required',
        ]);

        if($validator->fails()){
            goto error;
        }
        
        $users           = json_decode(json_encode($request->input('users')), true);
        $posts           = json_decode(json_encode($request->input('posts')), true);
        $postRanking     = json_decode(json_encode($request->input('post_ranking')), true);
        $reportedPost    = json_decode(json_encode($request->input('reported_post_block_parameters')), true);
        $alternativePost = json_decode(json_encode($request->input('alternative_post_weights')), true);
        $currentTime     = $request->input('current_time');

        $toogleArray = array("true", "false");
        if(!in_array($users['allow_auto_login'], $toogleArray)
            || !in_array($users['add_mob'], $toogleArray)
            || !in_array($users['custom_ad'], $toogleArray)){
            return response()->json(['status' => '400', 'error' => array('message' => 'Invalid data.')], 400);
        }

        if(!is_numeric($users['minimum_reach_points'])
          || !is_numeric($users['show_ads_interval'])
          || !is_numeric($posts['post_uploaded_point'])
          || !is_numeric($posts['post_retention_period'])
          || !is_numeric($posts['new_post_days'])
          || !is_numeric($postRanking['followers'])
          || !is_numeric($postRanking['repeat_limit'])
          || !is_numeric($postRanking['repeat_weight'])
          || !is_numeric($postRanking['new_age'])
          || !is_numeric($postRanking['age_gt_7'])
          || !is_numeric($postRanking['age_gt_29'])
          || !is_numeric($postRanking['age_gt_100'])
          || !is_numeric($reportedPost['percentage_of_views'])
          || !is_numeric($reportedPost['min_number_of_views'])
          || !is_numeric($alternativePost['within_10'])
          || !is_numeric($alternativePost['score_lt_20'])
          || !is_numeric($alternativePost['score_lt_30'])
          || !is_numeric($alternativePost['score_lt_40'])
          || !is_numeric($alternativePost['score_lt_50'])
          || !is_numeric($alternativePost['post_lt_y_days_old'])
          || !is_numeric($alternativePost['post_gt_x_days_old'])){
            return response()->json(['status' => '400', 'error' => array('message' => 'Please enter digits only.')], 400);
        }
   
        if ($validator->fails()) {
            error:
            return response()->json(['status' => '400', 'error' => array('message' => $validator->errors()->first())], 400);
        }

        $mergeConfig = array_merge($users, $posts, $postRanking, $reportedPost);
        try {
            $adminConfig = new AdminConfig();
            $postWeight  = new PostWeight();
            foreach($mergeConfig as $key => $config){   // update config table values
               
                $condition                  = $key;
                $dataToUpdate['value']      = (in_array($config, $toogleArray)) ? null : $config;
                $dataToUpdate['status']     = (in_array($config, $toogleArray)) ? (($config == "true") ? Constants::STATUS_TRUE : Constants::STATUS_FALSE) : Constants::STATUS_TRUE;
                $dataToUpdate['updated_at'] = $currentTime;
                $adminConfig->updateAdminConfig($condition, $dataToUpdate);
                $condition = "";
                $dataToUpdate = null;
            }

            foreach($alternativePost as $key => $config){   // update post weight table values
               
                $condition                  = $key;
                $dataToUpdate['weight']     = $config;
                $dataToUpdate['status']     = Constants::STATUS_TRUE;
                $dataToUpdate['updated_at'] = $currentTime;
                $postWeight->updateWeight($condition, $dataToUpdate);
                $condition = "";
                $dataToUpdate = null;
            }
            return response()->json(['status'  => '200', 'success' => array('message' => 'Admin config updated successfully.')], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => '500', 'error' => $e->getMessage() . $e->getLine() . $e->getFile() . $e], 500);
        }
    }
    /**
     * Get admin Config
     */
    public function getConfig(){
        
        $adminConfig = new AdminConfig();
        $postWeight  = new PostWeight();
        $getAdminConfig = $adminConfig->getConfig();
        $getWeight      = $postWeight->getWeight();
        
        $i = 0;
        $j = 0;
        $k = 0;
        $l = 0;
        $m = 0;
        $users = [];
        $posts = [];
        $postRanking   = [];
        $reportedPost  = [];
        $postWeights = [];
        foreach($getAdminConfig as $config){
            // set users data
            if($config['rule'] == "allow_auto_login" 
                || $config['rule'] == "add_mob" 
                || $config['rule'] == "custom_ad" 
                || $config['rule'] == "minimum_reach_points"
                || $config['rule'] == "show_ads_interval")
            {
                    $toogleStatus = "";
                    if($config['rule'] == "allow_auto_login" || $config['rule'] == "add_mob" || $config['rule'] == "custom_ad"){
                        $toogleStatus = ($config['status'] == 1) ? "true" : "false";
                    }
                    $users[$i]['rule']        =  $config['rule'];
                    $users[$i]['value']       =  ($toogleStatus == "") ? $config['value'] : null;
                    $users[$i]['status']      =  ($toogleStatus == "") ? Constants::STATUS_TRUE : $toogleStatus;
                    $users[$i]['created_at']  =  $config['created_at'];
                    $users[$i]['updated_at']  =  $config['updated_at'];
                    $i++;
            }
            // set posts data
            if($config['rule'] == "post_uploaded_point" 
                || $config['rule'] == "post_retention_period" 
                || $config['rule'] == "new_post_days" )
            {
                $posts[$j]['rule']        =  $config['rule'];
                $posts[$j]['value']       =  $config['value'];
                $posts[$j]['status']      =  Constants::STATUS_TRUE;
                $posts[$j]['created_at']  =  $config['created_at'];
                $posts[$j]['updated_at']  =  $config['updated_at'];
                $j++;
            }
            // set posts ranking
            if($config['rule'] == "followers" 
                || $config['rule'] == "repeat_limit" 
                || $config['rule'] == "repeat_weight"
                || $config['rule'] == "new_age"
                || $config['rule'] == "age_gt_7"
                || $config['rule'] == "age_gt_29"
                || $config['rule'] == "age_gt_100" )
            {
                $postRanking[$k]['rule']        =  $config['rule'];
                $postRanking[$k]['value']       =  $config['value'];
                $postRanking[$k]['status']      =  Constants::STATUS_TRUE;
                $postRanking[$k]['created_at']  =  $config['created_at'];
                $postRanking[$k]['updated_at']  =  $config['updated_at'];
                $k++;
            }
            // set reported post block parameters
            if($config['rule'] == "percentage_of_views" 
                || $config['rule'] == "min_number_of_views" )
            {
                $reportedPost[$l]['rule']        =  $config['rule'];
                $reportedPost[$l]['value']       =  $config['value'];
                $reportedPost[$l]['status']      =  Constants::STATUS_TRUE;
                $reportedPost[$l]['created_at']  =  $config['created_at'];
                $reportedPost[$l]['updated_at']  =  $config['updated_at'];
                $l++;
            }
        }

        foreach($getWeight as $weight){
            // set post weights
            if($weight['rule'] == "within_10" 
                || $weight['rule'] == "score_lt_20" 
                || $weight['rule'] == "score_lt_30"
                || $weight['rule'] == "score_lt_40"
                || $weight['rule'] == "score_lt_50"
                || $weight['rule'] == "post_lt_y_days_old"
                || $weight['rule'] == "post_gt_x_days_old" )
            {
                $postWeights[$m]['rule']        =  $weight['rule'];
                $postWeights[$m]['value']       =  $weight['weight'];
                $postWeights[$m]['status']      =  Constants::STATUS_TRUE;
                $postWeights[$m]['created_at']  =  $weight['created_at'];
                $postWeights[$m]['updated_at']  =  $weight['updated_at'];
                $m++;
            }
        }

        $response = array(
                        "users" => $users,
                        "posts"  => $posts,
                        "post_ranking"  => $postRanking,
                        "reported_post_block_parameters" => $reportedPost,
                        "alternative_post_weights" => $postWeights
                        );

        return response()->json(['status' => '200','success'=>  $response ], 200);                

    }

    
}
