<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Models\Notification;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * this function returns the notifications of the user
     */
    public function getNotifications(Request $request)
    {
        try {
            $user   =   Auth::user();
            $dateLimit  =   $request->input('date_limit') ? $request->input('date_limit') : Carbon::now(new DateTimeZone('UTC'))->format('Y-m');
            // $limit  =   $request->input('limit') ? $request->input('limit') : 10;
            // $offset  =   $request->input('offset') ? $request->input('offset') : 0;
            $notifcationModel   =   new Notification();
            $count              =   $user->notification->count();


            $data   =   $user->load(['notification' => function ($query) use ($dateLimit) {
                $query->with('notificationPost:id,file_url,thumb_url,media_type')
                ->where(DB::raw('DATE_FORMAT(created_at,"%Y-%m")'),$dateLimit)
                ->orderBy('created_at','asc');
            }]);

            // return response()->json([
            //     'status'   =>   200,
            //     'success'   =>  true,
            //     'message'   =>  'Data Fetched Successfully',
            //     'count'     =>  $count,
            //     'data'      =>  $data
            // ]);
            // dd($data);

            $dataForIds         =   $data;
            $ids                =   $dataForIds->notification->pluck('id');
            $updateStatus       =   $notifcationModel->updateNotifications($ids);

            $groupedNotification = [];
            foreach ($data->notification as $notification) {;
                $result['id'] = $notification['id'];
                $result['user_id'] = $notification['user_id'];
                $result['other_user_id'] = $notification['other_user_id'];
                $result['post_id'] = $notification['post_id'];
                $result['body'] = $notification['body'];
                $result['status'] = $notification['status'];
                $result['type'] = $notification['type'];
                $result['notification_post'] = $notification['notificationPost'];
                $result['created_at'] = $notification['created_at'];

                $date = date('Y-m-d', strtotime($result['created_at']));
                $groupedNotification[$date][] = $result;
            }

            return response()->json([
                'status'   =>   200,
                'success'   =>  true,
                'message'   =>  'Data Fetched Successfully',
                'count'     =>  $count,
                'data'      =>  $groupedNotification
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
     * This function returns the number of notification count of specific user
     */
    public function getNotificationCount(Request $request)
    {
        try {
            $user   =   Auth::user();
            $count  =   $user->loadCount('notification');
            $isnotify= $user->is_notify;
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'Data Fetched Successfully',
                'count'     =>  $count->notification_count,
                'is_notify' =>  $isnotify
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
     * This function is used to delete all the notification that has been readed and are older than 1 day / 24 hours
     */
    public function deleteOldNotifications()
    {
        $currentTime                =   Carbon::now(new DateTimeZone('UTC'))->format('Y-m-d h:m:s');
        $lastDay                    =   Carbon::now(new DateTimeZone('UTC'))->subDay()->format('Y-m-d h:m:s');

        $records                    =   Notification::where('status',Constants::NOTIFICATION_STATE_TYPE_READ)
                                                    ->where('updated_at','<=',$lastDay)
                                                    ->delete();

        // return response()->json([
        //     'status'    =>  200,
        //     'success'   =>  true,
        //     'message'   =>  'Data Fetched Successfully',
        //     'data'      =>  $records
        // ]);
    }
}
