<?php

namespace App\config;

class Constants
{
    //role ids
    public const ROLE_USER = 1;
    public const ROLE_ADMIN = 2;

    //countries
    public const COUNTRY_STATUS_ACTIVE  =   'active';
    public const COUNTRY_STATUS_IN_ACTIVE  =   'in-active';
    public const COUNTRY_TYPE_ACTIVE  =   1;
    public const COUNTRY_TYPE_IN_ACTIVE  =   2;


    //user statuses
    public const USER_STATUS_UNVERIFIED = 'un_verified';
    public const USER_STATUS_ACTIVE = 'active';
    public const USER_STATUS_INACTIVE = 'in_active';
    public const USER_STATUS_BANNED = 'banned';
    public const USER_STATUS_DELETED = 'deleted';
    public const USER_STATUS_PROFILE_INCOMPLETE = 'profile_incomplete';
    public const USER_STATUS_USER_DETAILS_INCOMPLETE = 'user_details_incomplete';
    public const USER_STATUS_ADMIN_PERMIT_REQUIRED = 'admin_permmision_required';
    public const USER_STATUS_PERMIT_PROFILE_INCOMPLETE = 'profile_incomplete_admin_permmision_required';
    public const POST_STATUS_ACTIVE = 'active';
    public const POST_STATUS_REPORTED = 'reported';
    public const POST_STATUS_HIDDEN = 'hidden';

    public const USER_STATUS_UNVERIFIED_INT = 0;
    public const USER_STATUS_ACTIVE_INT = 1;
    public const USER_STATUS_INACTIVE_INT = 2;
    public const USER_STATUS_BANNED_INT = 3;
    public const USER_STATUS_PROFILE_INCOMPLETE_INT = 4;
    public const USER_STATUS_USER_DETAILS_INCOMPLETE_INT = 5;
    public const USER_STATUS_ADMIN_PERMIT_REQUIRED_INT = 6;
    public const USER_STATUS_PERMIT_PROFILE_INCOMPLETE_INT = 7;
    public const USER_STATUS_DELETED_INT = 8;



    //posts status
    public const POSTS_TYPE_LUCKY_DIP = 'lucky_dip';
    public const POSTS_TYPE_FOLLOWERS = 'follower';
    public const POSTS_TYPE_ACTIVE    = 1;
    public const POSTS_TYPE_HIDDEN    = 2;
    public const POSTS_TYPE_REPORTED  = 3;
    public const POSTS_TYPE_REMOVED   = 4;
    public const POSTS_TYPE_SUSPENDED = 5;
    public const POSTS_TYPE_RELEASED  = 6;
    public const POSTS_STATUS_ACTIVE    = 'active';
    public const POSTS_STATUS_HIDDEN    = 'hidden';
    public const POSTS_STATUS_REPORTED  = 'reported';
    public const POSTS_STATUS_REMOVED   = 'removed';
    public const POSTS_STATUS_SUSPENDED = 'suspended';
    public const POSTS_STATUS_RELEASED  = 'released';

    //some general rules
    public const AUTO_LOGIN = 'allow_auto_login';

    //general status
    public const STATUS_ACTIVE = 1;
    public const STATUS_IN_ACTIVE = 2;

    // true false
    public const STATUS_TRUE = 1;
    public const STATUS_FALSE = 0;
    public const STATUS_TRUE_STR    = 'true';
    public const STATUS_FALSE_STR   = 'false';

    //MEDIA TYPES
    public const MEDIA_TYPE_IMAGE   =   'image';
    public const MEDIA_TYPE_VIDEO   =   'video';

    //admin config rules
    public const RULE_MINIMUM_REACH_POINTS = 'minimum_reach_points';

    //suspicious activity type
    public const SUSPICIOUS_ACTIVITY_INT_FAVOURITES        =   1;
    public const SUSPICIOUS_ACTIVITY_INT_REPLETION         =   2;
    public const SUSPICIOUS_ACTIVITY_INT_MAX_REPORTED      =   3;
    public const SUSPICIOUS_ACTIVITY_TYPE_FAVOURITES        =   'suspicious_favourites';
    public const SUSPICIOUS_ACTIVITY_TYPE_REPLETION         =   'suspicious_repletion';
    public const SUSPICIOUS_ACTIVITY_TYPE_MAX_REPORTED      =   'max_reported_content';

    //custom adds
    public const CUSTOM_ADDS_MEDIA_TYPE_IMAGE_INT                   =   1;
    public const CUSTOM_ADDS_MEDIA_TYPE_VIDEO_INT                   =   2;
    public const CUSTOM_ADDS_ACTION_CLICKABLE_INT                   =   1;
    public const CUSTOM_ADDS_ACTION_SWAPABLE_INT                    =   2;
    public const CUSTOM_ADDS_STATUS_TYPE_ACTIVE                     =   1;
    public const CUSTOM_ADDS_STATUS_TYPE_IN_ACTIVE                  =   2;
    public const CUSTOM_ADDS_STATUS_TYPE_DRAFT                      =   3;
    public const CUSTOM_ADDS_STATUS_TYPE_EXPIRED                    =   4;
    public const CUSTOM_ADDS_STATUS_ACTIVE                          =   'published';
    public const CUSTOM_ADDS_STATUS_IN_ACTIVE                       =   'in_active';
    public const CUSTOM_ADDS_STATUS_DRAFT                           =   'draft';
    public const CUSTOM_ADDS_STATUS_EXPIRED                         =   'expired';
        //custom adds category
        public const CUSTOM_ADDS_CATEGORY_STATUS_BASIC      = 1;
        public const CUSTOM_ADDS_CATEGORY_STATUS_PREMIUM    = 2;
        public const CUSTOM_ADDS_CATEGORY_TYPE_BASIC        = 'basic';
        public const CUSTOM_ADDS_CATEGORY_TYPE_PREMIUM      = 'premium';



    //Notification types
    public const NOTIFICATION_TYPE_AUTO_SUSPENDED                 =    1;
    public const NOTIFICATION_TYPE_ADMIN_SUSPENDED                =    2;
    public const NOTIFICATION_TYPE_POST_RELEASED                  =    3;
    public const NOTIFICATION_TYPE_POST_REACHED_TOP               =    4;
    public const NOTIFICATION_TYPE_POST_ACTIVE                    =    5;

    //NOTIFICATION STATE int
    public const NOTIFICATION_STATE_TYPE_UNREAD                   =     0;
    public const NOTIFICATION_STATE_TYPE_READ                     =     1;

    //NOTIFICATION STATE int
    public const NOTIFICATION_STATE_UNREAD                      =     'unread';
    public const NOTIFICATION_STATE_READ                        =     'read';
}
