<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CustomAddsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuspiciousActivityController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserTopicController;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

/**
 * Route model binding missing model object
 */
$missing = function()
{
    return response()->json([
        'status'    =>  400,
        'success'   =>  false,
        'error'     =>  'Please enter a valid id'
    ],400);
};

/**
 * test notification route
 */
$router->get('/notification/{user}', function (User $user, Request $request) use ($router) {
    try {
        // $user_id = $request->header('userId');
        $notification    = new Notification();
        $device_token = $user->device_token;
        $fcmMessage = 'This is test notification';
        $fcmData = array("delivery_id" => '1234');
        if ($device_token != null) {
            $notification->sendNotification(
                $device_token,
                $fcmMessage,
                $fcmData
            );
            return response()->json(['status' => '200', 'message' => 'Notication successfully send']);
        } else {
            return response()->json(['status' => '400', 'message' => 'Device token not found']);
        }
    } catch (\Exception $e) {
        return response()->json(['status' => '500', 'message' => 'Error sending notification']);
    }
});

Route::post('users/signup',[AuthController::class,'createUser']);
Route::post('users/userdetails',[AuthController::class,'createUserDetails']);


Route::get('user-login-status', function(){
    return response()->json([
        'status'    => 401,
        'success'   =>  false,
        'error'     => array('message' => 'you are not logged in, please login first'
        )], 401);
});
Route::post('users/login',[AuthController::class,'login'])->name('login');
Route::post('admin/login',[AuthController::class,'adminLogin']);

/**
 * User : Forgot password
 */
Route::post('users/reset-password',[AuthController::class,'requestResetPassword']);
Route::post('users/verification',[AuthController::class,'accountVerification']);
Route::post('users/change-password',[AuthController::class,'changePassword']);

Route::get('users/countries',[CountryController::class,'getAllCountries']);
Route::get('users/cities/{id}',[CityController::class,'getAllCities']);

Route::group(['middleware'=>'auth:api','prefix'=>'users/'], function () use ($missing){

    /**
     * Topics CRUD
     */
    Route::get('topics',[TopicController::class,'getAllTopics']);
    Route::post('user-topics',[UserController::class,'createUserTopics']);
    Route::get('mytopics',[UserController::class,'getMyTopics']);

    /**
     * Posts
     */
    Route::post('posts',[PostController::class,'getPosts']);
    Route::post('create-posts',[PostController::class,'createPost']);
    Route::get('post/{post}',[PostController::class,'getPostDetails'])->missing($missing);
    Route::get('profile/{user}',[UserController::class,'getPublicProfile'])->missing($missing);
    Route::get('hide-older-posts',[PostController::class,'hideOlderPosts']);

    /**
     * User Profile
     */
    Route::get('settings',[UserController::class,'getSettings']);
    Route::put('settings',[UserController::class,'updateSettings']);
    Route::delete('account',[UserController::class,'deleteUserAccount']);
    Route::put('notifications',[UserController::class,'toggleNotification']);
    Route::patch('logout',[AuthController::class,'Userlogout']);
    Route::put('update-password',[AuthController::class,'changeUserPassword']);

    /**
     * Follow any user
     */
    Route::put('follow',[UserController::class,'followUnfollowUser']);

    /**
     * Report any user
     */
    Route::post('report',[ReportController::class,'createReport']);

    /**
     * Top 100 Post API'S
    */
    Route::get('top',[PostController::class,'getTopPosts']);

    //route for cron job to mantain rankings by post topics and current month
    Route::post('ranking',[PostController::class,'updateRankings']);

    /**
     * My Favourites
     */
    Route::get('my-favourites',[UserController::class,'myFavourites']);

    /**
     * User Profile
     */
    Route::get('my-profile',[UserController::class,'getMyProfile']);
    Route::post('my-profile',[UserController::class,'updateMyProfile']);

    /**
     * User search and list
     */
    Route::get('users',[UserController::class,'getAllUsers']);

    /**
     * Custom Advertisements routes
     */
    Route::post('custom-ads',[CustomAddsController::class,'getUserCustomAds']);
    Route::post('custom-ads-clicks/{customAdds}',[CustomAddsController::class,'incrementClicks'])->missing($missing);

    /**
     * Notification
     */
    Route::get('notifications',[NotificationController::class,'getNotifications']);
    Route::get('notification-count',[NotificationController::class,'getNotificationCount']);
    Route::post('çron-notification',[NotificationController::class,'deleteOldNotifications']);

});

//admin routes
Route::group(['middleware' => 'App\Http\Middleware\CheckAdmin','prefix'=>'admin/'], function () use($missing) {
		Route::group(['middleware' => 'auth:api'], function () use($missing) {
			Route::put('config', [AdminSettingController::class,'updateConfig']);
            Route::get('get-config', [AdminSettingController::class,'getConfig']);
            /*
            * this routes for dashboard reports
            */
            Route::get('live-report', [DashboardController::class,'liveReport']);
            Route::get('non-live-report', [DashboardController::class,'nonLiveReport']);
            /*
            * this routes for user management
            */
            Route::get('user', [UserController::class,'getUsers']);
            Route::get('user-detail/{id}', [UserController::class,'UserDetail']);
            Route::put('update-user',[UserController::class,'UpdateUser']);

            /**
             * these routes are for content management
             */
            Route::get('cms',[PostController::class,'getCms']);
            Route::put('toggle-post',[PostController::class,'togglePost']);

            /**
             * These routes are for location management
             */
            Route::get('location',[CountryController::class,'getLocation']);
            Route::put('toggle-loc',[CountryController::class,'toggleLocation']);

            /**
             * These routes are for topic management
             */
            Route::get('topics',[TopicController::class,'getTopics']);
            Route::post('topics',[TopicController::class,'createTopic']);
            Route::put('topics/{topic}',[TopicController::class,'updateTopic'])->missing($missing);

            /**
             * These routes are for admin profile
             */
            Route::get('profile',[UserController::class,'getAdminProfile']);
            Route::post('profile',[UserController::class,'updateAdminProfile']);
            Route::put('password',[AuthController::class,'changeUserPassword']);

            Route::get('suspicious-activities',[SuspiciousActivityController::class,'getSuspiciousActivities']);
            /**
             * Task Schduling (Cronjob test routes)
             */
            Route::post('suspicious-activities',[SuspiciousActivityController::class,'addSuspiciousActivities']);
            Route::get('suspicious-activities',[SuspiciousActivityController::class,'getSuspiciousActivities']);

            /**
             * Custom adds management
             */
            Route::post('custom-ads',[CustomAddsController::class,'createAdds']);
            Route::get('custom-ads',[CustomAddsController::class,'getCustomAdds']);
            Route::put('custom-ads/{customAd}',[CustomAddsController::class,'toggleAd']);
            // Route::post('update-custom-ads/{customAdds}',[CustomAddsController::class,'updateCustomAdds'])->missing($missing);
            Route::post('update-custom-ads/{advertisementMapper}',[CustomAddsController::class,'updateCustomAdMapper'])->missing($missing);
            Route::delete('custom-ads/{advertisementMapper}',[CustomAddsController::class,'deleteCustomAdds'])->missing($missing);

            //auto disable (cronjob test route)
            Route::post('auto-disable',[PostController::class,'autoDisable']);

        });
});
