<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\changeUserPassword;
use App\Http\Requests\getAllUsers;
use App\Http\Requests\getUsers;
use App\Http\Requests\updateAdmin;
use App\Http\Requests\updateSettings;
use App\Http\Requests\userTopics;
use App\Models\AccessToken;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserSearches;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class UserController extends Controller
{
    //
    /**
     * Get User Profile
     */
    public function getSettings()
    {
        $user   =   Auth::user();
        $userModel  =   new User();
        $userData   =   $userModel->getUserSettings($user);
        if (count($userData) == 0) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'error' =>  'Error fetching user settings'
            ], 400);
        }
        return response()->json([
            'status' => 200,
            'success' => true,
            'message' =>  'Data fetched succesfully',
            'data'      =>  $userData
        ], 200);
    }

    /**
     * Update user profile
     */
    public function updateSettings(updateSettings $request)
    {
        try {
            $user   =   Auth::user();
            $userModel  =   new User();
            $usernames = $userModel->getUniqueUsername($user, $request);
            if ((($usernames) && ($usernames->id == $user->id)) || !$usernames) {
                $updatedData['username']            =   $request->input('username');
                $updatedData['dob']                 =   $request->input('dob');
                $updatedData['gender']              =   $request->input('gender');
                $updatedData['country_id']          =   $request->input('country_id');
                $updatedData['city']                =   $request->input('city');
                $updatedData['updated_at']          =   $request->input('current_time');
                DB::beginTransaction();
                $userData   =   $userModel->updateUser($user->id, $updatedData);
                if (!$userData) {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'error' =>  'Error fetching user settings'
                    ], 400);
                }
                DB::commit();
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' =>  'Data fetched succesfully',
                    'data'      =>  $userData
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'The username has already been taken'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Delete User Account
     */
    public function deleteUserAccount(Request $request)
    {
        try {
            $user   =   Auth::user();
            // $userModel  =   new User();
            // $currentTime                    =   $request->input('current_time');
            // $deletedUserData['username']    =   'deleteduser_' . $user->id;
            // $deletedUserData['email']       =   'deleteduser_' . $user->id . '@yopmail.com';
            // $deletedUserData['status']      =    Constants::USER_STATUS_DELETED_INT;
            // $deletedUserData['updated_at']  =   $currentTime;
            DB::beginTransaction();
            // $deletedUser    =   $userModel->updateUser($user->id, $deletedUserData);
            $deletedUser       =   User::find($user->id)->delete();
            if (!$deletedUser) {
                return response()->json([
                    'status'    =>   400,
                    'success'   =>  false,
                    'error'     =>  'Error deleting user'
                ], 400);
            }
            // $accessTokenModel   =   new AccessToken();
            // $destroyToken       =   $accessTokenModel->destroySessions($user->id);
            // $updatedRecords     =   $this->deleteUserAllPosts($user,$currentTime);

            DB::commit();
            response()->json([
                'status'    =>   200,
                'success'   =>  true,
                'message'   =>  'User Deleted Successfully'
            ], 200)->send();

            $postObj    =   new PostController();
            $postObj->updateRankings();
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Helper functiom to soft delete post of deleted users
     */
    public function deleteUserAllPosts($user, $currentTime)
    {
        $deletedUserData['file_url']    =   'deleted_media/deleted_post.png';
        $deletedUserData['thumb_url']   =   'deleted_media/deleted_post.png';
        $deletedUserData['media_type']  =   'picture';
        $deletedUserData['status']      =   Constants::POSTS_TYPE_REMOVED;
        $deletedUserData['updated_at']  =   $currentTime;
        $postModel                      =   new Post();
        $status                         =   $postModel->updateAllPost($user->id, $deletedUserData);
        return $status ? $status : 'false';
    }

    /**
     * Creating User Topics from default topics
     */
    public function createUserTopics(userTopics $request)
    {
        try {
            DB::beginTransaction();
            $user           =   Auth::user();
            $userId         =   $request->input('user_id');
            $topics         =   $request->input('def_topics_id');
            $currentTime    =   $request->input('current_time');

            //check if topic id exist
            $defTopicModel  =   new Topic();
            $checkIfTopicExist  =   $defTopicModel->checkTopicsExistence($topics);
            if (!$checkIfTopicExist) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Invalid topic id'
                ], 400);
            }

            // storing user topics ids
            $userModel      =   new User();
            $userTopics     =   $userModel->createUserTopics($user, $topics);
            if ($user->status == 'profile_incomplete') {
                $updateUserstatus['status']   =   Constants::USER_STATUS_ACTIVE_INT;
                $updateUserstatus['updated_at'] =   $currentTime;
                $updatedUser    =   $userModel->updateUser($userId, $updateUserstatus);
                if (!$updatedUser) {
                    return response()->json([
                        'status'    =>  400,
                        'success'   =>  false,
                        'error'     => 'Error updating user status'
                    ], 400);
                }
            }
            DB::commit();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'User topics created successfully'
            ]);
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
     * Toggle ON/OFF Notification
     */
    public function toggleNotification(Request $request)
    {
        try {
            $user   =   Auth::user();
            $userModel  =   new User();
            if ($user->is_notify == 'in-active') {
                $setNotification    =   Constants::STATUS_TRUE;
            } else {
                $setNotification    =   Constants::STATUS_FALSE;
            }
            $setNotificationData['is_notify']   =   $setNotification;
            $setNotificationData['updated_at']  =   $request->input('current_time');
            DB::beginTransaction();
            $updatedData    =   $userModel->updateUser($user->id, $setNotificationData);
            if (!$updatedData) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Error updating notification'
                ], 400);
            }
            DB::commit();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'error'     =>  'Notification status updated successfully'
            ]);
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

    public function followUnfollowUser(Request $request)
    {
        $user   =   Auth::user();
        $currentTime    =   $request->input('current_time');
        $otherUserId    =   $request->input('other_user_id');
        if ($user->id == $otherUserId) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'you can\'t follow your own profile'
            ], 400);
        }
        $otherUserExist =   User::find($otherUserId);
        if (!$otherUserExist) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Invalid other user id'
            ], 400);
        }
        $userModel  =   new User();
        $follow     =   $userModel->toggleFollow($user, $otherUserId);
        $message    =   (count($follow['attached']) == 1) ? "followed" : "unfollowed";
        return response()->json([
            'status'        =>  200,
            'success'       =>  true,
            'message'       =>  'User ' . $message . ' Sucessfully'
        ]);
    }

    /**
     * get my topics
     */
    public function getMyTopics()
    {
        $user   =   Auth::user();
        $age        =   Carbon::parse($user->dob)->diff(Carbon::now())->y;
        $userId =   $user->id;
        $myTopics = Topic::with(['topicUsers' => function ($query) use ($userId) {
            $query->select('users.id', 'username')->whereRaw('user_id = ' . $userId);
        }])->where('status', Constants::STATUS_ACTIVE)->where(function ($query) use ($age) {
            if ($age <= 18) {
                $query->where('is_age_limit', Constants::STATUS_FALSE);
            }
        })->get();

        $colors = ['#F56258', '#F5BA3C', '#F15628', '#3EB495'];
        $myTopics = $myTopics->toArray();
        $index = 0;
        $topicWithColor = [];
        foreach ($myTopics as $topic) {
            $colorCode['color']  = $colors[$index];
            array_push($topicWithColor, array_merge($topic, $colorCode));
            $index++;
            if ($index == 3) {
                $index = 0;
            }
        }
        if (!$myTopics) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Error fetching user topics'
            ]);
        }
        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data fetched successfully',
            'data'      =>  $topicWithColor
        ]);
    }
    /*
    * Landing on the user management tab will open a table view of all the registered users with basic information being displayed. There will be an action button to disable the user by the admin. There will be a search option for admin to search a user via username or user id.

    *  Admin should be able to view all the registered users in table view.
    *  Admin should be able to block/disable the users from the list view.
    *  If auto login is disabled by admin, then the user list will also show the list waiting users.
    *  An email will be sent to the users over their email id that their account registration is pending. Waiting for admin approval. Once allowed, then new users will be able to login.
    *  Admin should be able to view all the users that have newly registered over the application and their profiles reqiure admin approval in table view.
    *  Admin should be able to approve/deny the users from the list view. "
    */
    public function getUsers(getUsers $request)
    {
        $type       = $request->query('type');
        $limit      = $request->query('limit');
        $offset     = $request->query('offset');
        $search     = $request->query('search');
        $user       = new User();

        if (!$limit) {
            $limit = 10;
        }
        if (!$offset) {
            $offset = 0;
        }
        $getUsers   = $user->getUsers($type, $limit, $offset, $search);

        return response()->json([
            'status'      =>  200,
            'success'     =>  true,
            'message'     =>  'Data fetched successfully',
            'total_count' =>    $getUsers['count'],
            'data'        =>  $getUsers['data']
        ]);
    }
    /*
    *  "Clicking on any user name will open the detail view of the user. On the full profile view, admin will be able to view all details of the users along with the posts.
    *   Admin should be able to view full detail page of user.
    *   All user information should be maintained/calculated in the database.
    *   Admin should be able to view all user’s posts.
    */
    public function UserDetail(Request $request, $id)
    {
        $user       = new User();
        $type       = $request->query('type');
        $getUsers   = $user->getUserDetail($id, $type);
        if ($type != 'top') {
            $result = [];
            $result['id']             = $getUsers['user'][0]['id'];
            $result['profile_image']  = $getUsers['user'][0]['profile_image'];
            $result['thumb_image']    = $getUsers['user'][0]['thumb_image'];
            $result['posts']          = ($getUsers['post']) ? $getUsers['post'] : [];
        }

        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data fetched successfully',
            'data'      =>  $getUsers
        ]);
    }
    /*
    * This method use for update user status.
    */
    public function UpdateUser(Request $request)
    {
        $type         = $request->input('type');
        $userId       = $request->input('user_id');
        $status       = $request->input('status');
        $currentTime  = $request->input('current_time');

        $userModel = new User();
        $checkUserExist = User::find($userId);
        $email = $checkUserExist['email'];
        if (!$checkUserExist) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Invalid user id'
            ]);
        }
        if ($type == "profile") {
            $accessTokenModel   =   new AccessToken();
            $status = $status == "true" ? Constants::USER_STATUS_ACTIVE_INT : Constants::USER_STATUS_INACTIVE_INT;
            $dataToUpdate['status']     = $status;
            $dataToUpdate['updated_at'] = $currentTime;
            $userModel->updateUserStatus($userId, $dataToUpdate);
            $token  =   $accessTokenModel->destroySessions($userId);
            return response()->json([
                'status'        =>  200,
                'success'       =>  true,
                'message'       =>  'User sucessfully updated.'
            ]);
        }

        if ($type == "registration") {

            $toogleArray = array("true", "false");
            if (!in_array($status, $toogleArray)) {
                return response()->json(['status' => '400', 'error' => array('message' => 'Invalid data.')], 400);
            }

            if ($status == "true") {
                $status = ($checkUserExist['status'] == Constants::USER_STATUS_ADMIN_PERMIT_REQUIRED)
                    ? Constants::USER_STATUS_ACTIVE_INT
                    : Constants::USER_STATUS_PROFILE_INCOMPLETE_INT;
                $dataToUpdate['status'] = $status;

                Mail::raw("Your registration is approved by Admin.", function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Registration Approve - FindUr App')->from(env('MAIL_FROM'));
                });
            } else {
                Mail::raw("Your registration is denied by Admin.", function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Registration Denied - FindUr App')->from(env('MAIL_FROM'));
                });
                $dataToUpdate['username'] =  'findUrApp_' . $userId;
                $dataToUpdate['email']    =  'findUrApp_' . $userId . '@yopmail.com';
                $dataToUpdate['status']   =  Constants::USER_STATUS_DELETED_INT;
            }

            $dataToUpdate['updated_at'] = $currentTime;
            $userModel->updateUserStatus($userId, $dataToUpdate);

            return response()->json([
                'status'        =>  200,
                'success'       =>  true,
                'message'       =>  'User sucessfully updated.'
            ]);
        }
    }

    /**
     *
     *  The public profile is the section where users can view other users’ profile. This will be a non-editable screen where only information is show cased.
     *  The public profile will display the user image, username, country name, bio, number of followers and  an option(button) to 'follow' that user. If the user is already being followed then the button text will be 'following'
     *  The live and top 100 posts of that user will be displayed are sorted based on the date uploaded only.
     *  By tapping on the post details users will have the provision to ‘share’ and ‘report’ and ‘compare with favorite’ by clicking on the button and other post details such highest and current ranks and topic details and date.
     */
    public function getPublicProfile(User $user, Request $request)
    {
        $authUser           =   Auth::user();
        $type               =   $request->input('type');
        $limit              =   $request->input('limit') ? $request->input('limit') : 10;
        $offset             =   $request->input('offset') ? $request->input('offset') : 0;
        $search             =   $request->input('search');
        $like               =   $request->input('like');

        //creating recent searches
        if ($search == true && $like) {
            $searchData['user_id']          =   $authUser->id;
            $searchData['searchable_id']    =   $user->id;
            if ($authUser->user_search->contains($user->id)) {
                $authUser->user_search()->detach($user->id);
                $authUser->user_search()->attach($user->id);
            } else {
                $authUser->user_search()->attach($user->id);
            }
        }

        if ($type == 'post') {

            $count['count']              =   $user->loadCount(['post' => function ($query) {
                $query->whereNotIn('status', [Constants::POSTS_TYPE_HIDDEN, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED]);
            }])->post_count;

            $data               =   $user->loadCount('follower')->load(['country', 'post' => function ($query) use ($limit, $offset) {
                $query->whereNotIn('status', [Constants::POSTS_TYPE_HIDDEN, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED])
                    ->take($limit)->skip($offset);
            }, 'post.postTopic:id,name', 'post.postRanking'])->toArray();
        } else if ($type == 'top') {

            $count['count']              =   $user->loadCount(['post' => function ($query) {
                $query->whereNotIn('status', [Constants::POSTS_TYPE_HIDDEN, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED])->whereHas('postRanking');
            }])->post_count;

            $userPostRanking    =   $user->loadCount('follower')->load(['country', 'post' => function ($query) use ($limit, $offset) {
                $query->whereNotIn('status', [Constants::POSTS_TYPE_HIDDEN, Constants::POSTS_TYPE_REMOVED, Constants::POSTS_TYPE_SUSPENDED])->take($limit)->skip($offset)->whereHas('postRanking');
            }, 'post.postTopic:id,name', 'post.postRanking']);
            $data =  $userPostRanking->toArray();
        } else {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Please enter the valid type'
            ], 400);
        }

        $flag['is_follow']  =   $user->follower->contains($authUser->id);
        $response           =    array_merge($data, $flag, $count);
        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data fetched successfully',
            'data'      =>   $response
        ]);
    }

    /**
     *
     * As a user, I should be able to view a list of my current and previous favourites posts according to the applied filters.
     *      All favorite marked posts should be displayed.
     *      Posts that are expired should only show the information but the thumbnail will not be displayed.
     *      Each post should display the basic information in the list view.
     *      Date (date of favorite marked)
     *      Current Rank
     *      Highest Rank
     *      Topic
     *      Tapping on live/top 100 favorite posts should open the full view of the post.
     *      Full view of the post should display the full details.
     *      Users should be able to filter the results (location & topic).
     */
    public function myFavourites(Request $request)
    {
        try {
            $user           =   Auth::user();
            $userModel      =   new User();
            $limit          =   $request->input('limit') ? $request->input('limit') :  10;
            $offset          =   $request->input('offset') ? $request->input('offset') :  0;
            $location       =   $request->query('location') == 'all' ? "" : $request->query('location');
            $topic          =   $request->query('topic');
            $postController =   new PostController();
            $topic          =   $postController->getRequestedTopic($topic, $user->id);
            $data           =   $userModel->getMyFavourites($user, $topic, $location, $limit, $offset);

            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Data Fetched Successfuly',
                'data'      =>  $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }


    /**
     * As a user, I should be able to view my profile by tapping on the profile icon from the top of the screen.
     * The personal profile can be accessed from the top right profile icon. Tapping on this will take the user to their own profile. If the user wants to change their basic details like username, email, country, password, DOB) it will be available in the settings tab. The personal profile will have the following information:
     *   User profile image (Editable)
     *   User bio information (Editable) - by tapping on the edit icon will open up a popup with a free text field (300 max characters)
     *   Basic Information (Username, Country) - will be editable from the settings menu
     *   Follower’s count – Tapping on this will open a list of all users who are following, if the user taps on the username their profile view will appear on the screen
     *   Following count – Tapping on this will open a list of all users who this user is following, if the user taps on the username their profile view will appear on the screenThe profile will also showcase all the posts that had been uploaded by the user.

     * As a user, I should be able to view a list of my uploaded post.
     *   The user will have the option to filter the posts listing with the following options:
     *   All – This will be a list that will show all the posts the user has ever posted
     *   Live Posts – The posts that are currently not expired or had been ranked in top 100. (Default view)
     *   Top 100 posts – The posts that had reached the rankings in top 100.
     *   Removed – Posts that are blocked or removed after not being in the top 100 for the first 28 days will be displayed here
     *   Tapping on any post will open the detail view of the post.

     * As a user, I should be able to view public profile by tapping on the username of the other users.
     *   The public profile is the section where users can view other users’ profile. This will be a non-editable screen where only information is show cased.
     *   The public profile will display the user image, username, country name, bio, number of followers and  an option(button) to 'follow' that user. If the user is already being followed then the button text will be 'following'
     *   The live and top 100 posts of that user will be displayed are sorted based on the date uploaded only.
     *   By tapping on the post details users will have the provision to ‘share’ and ‘report’ and ‘compare with favorite’ by clicking on the button and other post details such highest and current ranks and topic details and date.
     */

    public function getMyProfile(Request $request)
    {
        try {
            $user                       =   Auth::user();
            $userModel                  =   new User();
            $userId                     =   $user->id;
            $type                       =   $request->input('type');
            $limit                      =   $request->input('limit') ? $request->input('limit') : 10;
            $offset                      =   $request->input('offset') ? $request->input('offset') : 0;
            $usersData                  =   $user->loadCount('follower', 'follows')->load('country');
            // $usersData                  =   $user->loadCount('follower', 'follows')->load(['follower', 'follows']);

            if ($type == 'all_post') {

                $userAllPost                =   $userModel->getUserAllPosts($userId, $limit, $offset);
                $data = ['user_data' => $usersData, 'count' => $userAllPost->post_count, 'attribute' => $userAllPost->post];
            } else if ($type == 'live_post') {
                $userPostLive               =   $userModel->getUserPostsLive($userId, $limit, $offset);
                $data = ['user_data' => $usersData, 'count' => $userPostLive->post_count, 'attribute' => $userPostLive->post];
            } else if ($type == 'top') {

                $userPostRanking            =   $userModel->getUserPostRanking($userId, $limit, $offset);
                $data = ['user_data' => $usersData, 'count' => $userPostRanking->post_count, 'attribute' => $userPostRanking->post];
            } else if ($type == 'removed_posts') {

                $userPostRemoved            =   $userModel->getUserPostsRemoved($userId, $limit, $offset);
                $data = ['user_data' => $usersData, 'count' => $userPostRemoved->post_count, 'attribute' => $userPostRemoved->post];
            } else if ($type ==  'follower') {

                $follower                  =   $usersData->load(['follower' => function ($query) use ($offset, $limit) {
                    $query->take($limit)->skip($offset);
                }]);

                $data = ['user_data' => $usersData];
            } else if ($type ==  'follows') {

                $follows                  =   $usersData->load(['follows' => function ($query) use ($offset, $limit) {
                    $query->take($limit)->skip($offset);
                }]);

                $data = ['user_data' => $usersData];
            } else {

                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Please enter the valid type'
                ], 400);
            }

            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  "Data fetched Successfuly",
                'data'      =>  $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function updateMyProfile(Request $request)
    {
        try {
            $user           =   Auth::user();
            $userId         =   $user->id;
            $bioDetails     =   $request->input('bio_data');
            $currentTime    =   $request->input('current_time');

            //update profile image of user
            if ($request->hasFile('profile_image')) {
                $imageUrl    =  $user->profile_image;
                $thumbUrl    =  $user->thumb_image;
                $imagePath = explode('.com/', $imageUrl);
                $thumbPath = explode('.com/', $thumbUrl);

                //if image other than dummy image delete it first
                if ($imagePath[1] != "user_images/dummy_image.png") {
                    $fileExists =   Storage::disk('s3')->exists($imagePath[1]);
                    if ($fileExists) {
                        $deleteImage    =   Storage::disk('s3')->delete($imagePath[1]);
                        $thumbFileExists     =   Storage::disk('s3')->exists($thumbPath[1]);
                        if ($thumbFileExists) {
                            $deleteImage    =   Storage::disk('s3')->delete($thumbPath[1]);
                        }
                    }
                }


                //uploading a new user image and thumb image
                $image          = $request->file('profile_image');
                $extension      = $image->getClientOriginalExtension();
                $imageName      = $image->getClientOriginalName();
                $imageName      = str_replace(' ', '_', $imageName);
                $fileNameWithoutExt = pathinfo($imageName, PATHINFO_FILENAME);
                $destinationPath = base_path('public/user_images/user_' . $userId . '');
                if (!file_exists($destinationPath)) {
                    //create folder
                    mkdir($destinationPath, 0777, true);
                }
                $time           = time();
                $imageUrl       = $fileNameWithoutExt . '_' . $time . '.' . $extension;
                $image->move($destinationPath, $imageUrl);

                //generating thumbnail
                $image          = ImageManagerStatic::make($destinationPath . '/' . $imageUrl)->resize('550', '340');
                $thumbImageUrl  = '/thumb_' . $fileNameWithoutExt . '_' . $time . '-' . '550x340' . '.' . $extension;
                $image->save($destinationPath . $thumbImageUrl);

                $urlImage = $destinationPath . '/' . $imageUrl;
                $urlThumb = $destinationPath . $thumbImageUrl;

                //s3 Configurations
                $imagePath = 'user_images/user_' . $userId . '/' . $imageUrl;
                $thumbPath = 'user_images/user_' . $userId . '' . $thumbImageUrl;
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

                $dataToUpdate['profile_image']         = $imagePath;
                $dataToUpdate['thumb_image']           = $thumbPath;
            }

            $dataToUpdate['bio_details']    =   $bioDetails;
            $dataToUpdate['updated_at']     =   $currentTime;
            $userModel  =   new User();
            DB::beginTransaction();
            $updatedUser    =   $userModel->updateUser($userId, $dataToUpdate);
            if (!$updatedUser) {
                return response()->json([
                    "status"    =>  400,
                    "success"   =>  false,
                    "error"     =>  "Error updating user"
                ], 400);
            }
            DB::commit();

            return response()->json([
                "status"    =>  200,
                "success"   =>  true,
                "message"     =>  "User updated successfuly"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * As an admin user, I should be able to view my profile details.
     *   Admin can view their own profile by tapping on user icon in the side bar. Following information will be displayed in the user own profile:
     *   Admin Profile Image
     *   User Name
     *   Email Id
     *   Change Password (button)
     *   Edit Profile (button)
     */
    public function getAdminProfile()
    {
        try {

            $admin  =   Auth::user();
            return response()->json(
                [
                    'status'    =>  200,
                    'success'   =>  true,
                    'message'   =>  'Data Fetched Successfully',
                    'data'      =>  $admin
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }


    /**
     * As an admin user, I should able to edit my profile details.
     *   Admin can edit their own profile by tapping on edit icon in the Profile View. Following information will be displayed in the user own profile:
     *   Admin Profile Image
     *   User Name
     *   Email Id


     *   Validation:
     *   Admin Profile Image (15 MB max - PNG, JPEG)
     *   Username (Alphabets and Number - 30 max)
     *   If any of the fields are left empty: ''Required fields can not be left empty.''
     */
    public function updateAdminProfile(updateAdmin $request)
    {
        try {

            $admin          =   Auth::user();
            $adminId        =   $admin->id;
            $userName       =   $request->input('username');
            $currentTime    =   $request->input('current_time');

            $userModel  =   new User();
            $usernames = $userModel->getUniqueUsername($admin, $request);
            if ((($usernames) && ($usernames->id == $admin->id)) || !$usernames) {

                //update profile image of user
                if ($request->hasFile('profile_image')) {
                    $imageUrl    =  $admin->profile_image;
                    $thumbUrl    =  $admin->thumb_image;
                    $imagePath = explode('.com/', $imageUrl);
                    $thumbPath = explode('.com/', $thumbUrl);

                    //if image other than dummy image delete it first
                    if ($imagePath[1] != "user_images/dummy_image.png") {
                        $fileExists =   Storage::disk('s3')->exists($imagePath[1]);
                        if ($fileExists) {
                            $deleteImage    =   Storage::disk('s3')->delete($imagePath[1]);
                            $thumbFileExists     =   Storage::disk('s3')->exists($thumbPath[1]);
                            if ($thumbFileExists) {
                                $deleteImage    =   Storage::disk('s3')->delete($thumbPath[1]);
                            }
                        }
                    }


                    //uploading a new user image and thumb image
                    $image          = $request->file('profile_image');
                    $extension      = $image->getClientOriginalExtension();
                    $imageName      = $image->getClientOriginalName();
                    $imageName      = str_replace(' ', '_', $imageName);
                    $fileNameWithoutExt = pathinfo($imageName, PATHINFO_FILENAME);
                    $destinationPath = base_path('public/user_images/user_' . $adminId . '');
                    if (!file_exists($destinationPath)) {
                        //create folder
                        mkdir($destinationPath, 0777, true);
                    }
                    $time           = time();
                    $imageUrl       = $fileNameWithoutExt . '_' . $time . '.' . $extension;
                    $image->move($destinationPath, $imageUrl);

                    //generating thumbnail
                    $image          = ImageManagerStatic::make($destinationPath . '/' . $imageUrl)->resize('550', '340');
                    $thumbImageUrl  = '/thumb_' . $fileNameWithoutExt . '_' . $time . '-' . '550x340' . '.' . $extension;
                    $image->save($destinationPath . $thumbImageUrl);

                    $urlImage = $destinationPath . '/' . $imageUrl;
                    $urlThumb = $destinationPath . $thumbImageUrl;

                    //s3 Configurations
                    $imagePath = 'user_images/user_' . $adminId . '/' . $imageUrl;
                    $thumbPath = 'user_images/user_' . $adminId . '' . $thumbImageUrl;
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

                    $dataToUpdate['profile_image']         = $imagePath;
                    $dataToUpdate['thumb_image']           = $thumbPath;
                }

                $dataToUpdate['username']       =   $userName;
                $dataToUpdate['updated_at']     =   $currentTime;

                DB::beginTransaction();

                $updatedAdmin   =   $userModel->updateUser($adminId, $dataToUpdate);
                if (!$updatedAdmin) {
                    return response()->json([
                        'status'    =>  400,
                        'success'   =>  false,
                        'error'     =>  "Error updating admin"
                    ], 400);
                }

                DB::commit();

                return response()->json([
                    'status'    =>  200,
                    'success'   =>  true,
                    'message'   =>  "Admin updated successfully",
                    'data'      =>  $updatedAdmin
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'The username has already been taken'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * As a user, I should be able to search other user profiles on the application.
     *   The user will be able to search other user profile by their usernames.
     *   By tapping on the search icon from the navigation bar, the user is redirected to the search screen. User types in the username to search other profiles. User can also clear the previously searched items from the list by tapping on the "Clear Recent Searches" option.
     *
     *   Search Field (Textfield - 30 characters allowed)
     */
    public function getAllUsers(getAllUsers $request)
    {
        $user           =   Auth::user();
        $userModel      =   new User();
        $recentSearch   =   $request->input('recent_search');

        //clear recent searches
        if ($recentSearch == "clear") {

            $flag = $user->user_search()->detach();
            $data       =   $user->user_search();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Recent Searches Cleared Successfully',
                'count'     =>  0,
                'data'      =>  []
            ]);
        } else if ($recentSearch == 'true') {

            //get user recent searches
            $data       =   $user->loadCount('user_search')
            ->load(['user_search'=>function($query)
            {
                $query->limit(10);
            }]);

            $alignedData = [];
            $i = 0;
            $count  =   $data->user_search_count;
            $data = $data->user_search;
            foreach ($data as $search) {
                $alignedData[$i]['id']              =   $search->id;
                $alignedData[$i]['username']        =   $search->username;
                $alignedData[$i]['profile_image']   =   $search->profile_image;
                $alignedData[$i]['thumb_image']     =   $search->thumb_image;
                $i++;
            }

            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Data Fetched Successfully',
                'count'     =>  $count,
                'data'      =>  $alignedData
            ]);
        } else {

            $like           =   $request->input('like');
            $offset         =   $request->input('offset') ? $request->input('offset') : 0;
            $limit          =   $request->input('limit') ? $request->input('limit') : 10;
            $usersWithoutCount          =   $userModel->getAllUsers($user, $like, $limit, $offset);
            $countData      =   $usersWithoutCount;
            $data           =   $usersWithoutCount->take($limit)->skip($offset)->get();
            $count          =   $countData->count();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Data Fetched Successfully',
                'count'     =>  $count,
                'data'      =>  $data
            ]);
        }
    }
}
