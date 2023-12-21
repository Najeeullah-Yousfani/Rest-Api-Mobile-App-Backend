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
use App\Models\Post;
use App\Models\PostReaction;
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

class DashboardController extends Controller
{
    /**
    * "There will be graghs for the followings:
    * Total no. of Users - Count of Active/Inactive
    * Total no. of downloads - Android/iOS
    * Total no. of Posts by Topic
    * Total no. of Posts in Topic - Live/Reported/Removed posts - Filter (topics dropdown - Single selection)
    * No. of Reports - Total no of Reported/Removed posts
    * No. of Reported Posts - Suspended/Removed/Released posts
    * Sales by Topic
    * Sales by Region - Country
    * Posts Selected - Fav/Alternate
    * Live = Active post
    * Suspended = Auto-Disabled Post
    * Reported = Posts reported by app users
    * Removed = Disable
    * Released = Posts enabled by admin after auto-disable"
    */
    public function liveReport(Request $request){

        $country     = ($request->query('country')) ? explode(",", $request->query('country')) : [];
        $gender      = $request->query('gender');
        $age         = $request->query('age');
        $start_date  = $request->query('start_date');
        $end_date    = $request->query('end_date');
        $userModel   = new User;
        $postModel   = new Post();

        $totalUsers         = $userModel->getTotalUsersCount($country, $gender, $age, $start_date, $end_date);     // Total Users Count active/inactive.
        $totalDownloads     = $userModel->getTotalDownloads($country, $gender, $age, $start_date, $end_date);      // Total Downloads Count android/ios.
        $liveReportedHidden = $postModel->getNoOfPost($country, $gender, $age, $start_date, $end_date);            // Total number posts live/reported/hidden.
        $noOfPostByTopic    = $postModel->getNoOfPostByTopic($country, $gender, $age, $start_date, $end_date);     // Total number of posts by topic.
        $noOfPostInTopic    = $postModel->getNoOfPostInTopic($country, $gender, $age, $start_date, $end_date);     // Total number posts in topic.
        $reports            = $postModel->getPostReports($country, $gender, $age, $start_date, $end_date);         // Total reported posts reported/removed.
        $noOfReportedPost   = $postModel->getNoOfReportedPost($country, $gender, $age, $start_date, $end_date);    // Total reported post suspended/removed/released.
        $saleByTopic        = $postModel->getNoOfPostsByTopic($country, $gender, $age, $start_date, $end_date);    // Total posts in topic.
        $saleByCountry      = $postModel->getNoOfPostsByCountry($country, $gender, $age, $start_date, $end_date);  // Total posts in country.
        $postSelected       = $postModel->getNoOfPostsSelected($country, $gender, $age, $start_date, $end_date);   // Total post favourite/unfavourite.

        $response = array(
                        "active_user" => $totalUsers['active_users'],
                        "inactive_user" => $totalUsers['inactive_users'],
                        "no_of_users" => $totalUsers['no_of_users'],
                        "android" => $totalDownloads['android'],
                        "ios" => $totalDownloads['ios'],
                        "no_of_downloads" => $totalDownloads['no_of_downloads'],
                        "live_reported_hidden" => $liveReportedHidden,
                        "no_of_post_by_topic" => $noOfPostByTopic,
                        "no_of_post_in_topic" => $noOfPostInTopic,
                        "reports" => $reports,
                        "reported_posts" => $noOfReportedPost,
                        "topic" => $saleByTopic,
                        "country" => $saleByCountry,
                        "post_selected" => $postSelected,
                         );

        return response()->json(['status' => '200','success'=>  $response ], 200);

    }
    /*
    * "These reports are generated on monthly basis.
    *  The data will be updated every month.
    *  By default, the data will be of 'current month' , there will be a dropdown over the top of the graph from where admin can select a month and the graph will be updated according to the selected month. (single select)
    * There will be graghs for the followings:

    * Interactions - Fav selected/Post Selected/Post Clicked/Ads Seen
    * User Visits Monthly - count of active user monthly (0-05, 5-10, 10-15, 15-25, 25+)
    * Post Score - count of post scores monthly (<50, 51-70, 71-80, 81-90, 91-100)"
    */
    public function nonLiveReport(Request $request){

        $country       = ($request->query('country')) ? explode(",", $request->query('country')) : [];
        $gender        = $request->query('gender');
        $age           = $request->query('age');
        $start_date    = $request->query('start_date');
        $end_date      = $request->query('end_date');
        $userModel     = new User;
        $postModel     = new Post();
        $postReaction  = new PostReaction();

        if($start_date == "" && $end_date == ""){
            $start_date = date('Y-m-01'); // hard-coded '01' for first day
            $end_date  = date('Y-m-t');
        }

        $totalUsers         = $userModel->getTotalUsersCount($country, $gender, $age, $start_date, $end_date);     // Total Users Count active/inactive.
        $totalDownloads     = $userModel->getTotalDownloads($country, $gender, $age, $start_date, $end_date);      // Total Downloads Count android/ios.
        $liveReportedHidden = $postModel->getNoOfPost($country, $gender, $age, $start_date, $end_date);            // Total number posts live/reported/hidden.
        $interactions       = $postModel->getInteraction($country, $gender, $age, $start_date, $end_date);         // Total interactions favourite/post seen/post click/ads seen.
        $userVisitsMonthly  = $postReaction->getUserVisitsMonthly($country, $gender, $age, $start_date, $end_date);// Total User Visits monthly.
        $postScore          = $postModel->getPostScores($country, $gender, $age, $start_date, $end_date);           // Total Post Score by month.

        $response = array(
                        "active_user" => $totalUsers['active_users'],
                        "inactive_user" => $totalUsers['inactive_users'],
                        "no_of_users" => $totalUsers['no_of_users'],
                        "android" => $totalDownloads['android'],
                        "ios" => $totalDownloads['ios'],
                        "no_of_downloads" => $totalDownloads['no_of_downloads'],
                        "live_reported_hidden" => $liveReportedHidden,
                        "interactions" => $interactions,
                        "user_visits_monthly" => $userVisitsMonthly,
                        "post_score" => $postScore,
                         );

        return response()->json(['status' => '200','success'=>  $response ], 200);

    }

}
