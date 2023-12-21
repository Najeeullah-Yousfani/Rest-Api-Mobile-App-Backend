<?php

namespace App\Models;

use App\config\Constants;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class Notification extends Model
{
    use Authenticatable, Authorizable;
    protected $table = "notifications";
    protected $guarded = [];
    protected $casts = [
        'other_user_id'      => 'int',
        'user_id'            => 'int',
        'travelling_plan_id' => 'int',
        'delivery_id'        => 'int',
        'bid_id'             => 'int',
    ];


    /*
    * This method is used as an accessor for status
    */
    public function getStatusAttribute($value)
    {
        switch ($value) {
            case 0:
                $value = "unread";
                break;
            case 1:
                $value = "read";
                break;
        }
        return $value;
    }

    /**
     * This method is used as an accessor for notification type
     */
    public function getTypeAttribute($value)
    {

        switch ($value) {
            case 1:
                $value = "auto-suspended";
                break;
            case 2:
                $value = "admin-suspended";
                break;
            case 3:
                $value = "post-released";
                break;
            case 4:
                $value = "top-ranked";
                break;
            case 5:
                $value = "post-active";
                break;
        }
        return $value;
    }



    public function notificationUser()
    {
        return $this->belongsTo(User::class);
    }
    public function notificationPost()
    {
        return $this->belongsTo(Post::class,'post_id');
    }

    public function sendNotification($deviceToken = null, $message = null, $data = null)
    {
        $serverKey = env('FCM_SERVER_KEY');
        $title              = 'Find-UR';
        $fcmMsg = array(
            'title' => $title,
            'body' => $message,
            'data' => $data
        );

        $fcmFields = array(
            'to' => $deviceToken,
            'priority' => 'high',
            'data' => $data,
            'notification' => $fcmMsg,
        );
        $headers = array(
            "Authorization: key=$serverKey",
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
        $result = curl_exec($ch);
        curl_close($ch);
        //return $result ;
    }

    public function createNotification($data)
    {
        return $record =   Notification::create($data);
    }

    //changing the status to read if unread
    public function updateNotifications($data)
    {
        return $updateStatus       =   Notification::whereIn('id', $data)
            ->where('status', Constants::NOTIFICATION_STATE_TYPE_UNREAD)
            ->update([
                'status'    =>  Constants::NOTIFICATION_STATE_TYPE_READ
            ]);
    }
}
