<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\createAdds;
use App\Http\Requests\updateCustomAdds;
use App\Models\advertisementMapper;
use App\Models\AdvertisementReaction;
use App\Models\country;
use App\Models\CustomAdds;
use App\Models\CustomAddsGender;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class CustomAddsController extends Controller
{
    //
    /**
     *As an admin, I should be able to have a full functionality control of create custom ads
     *   The ad creation feature will be on top with set fields that will allow the admin to create and publish ads. Certain parameters will be required to create and publish ads. These parameters will define the target audience of the created ad.
     *   Upload Content – Default uploader will open and admin can select any image or video to upload. Once uploaded, a thumbnail will be generated.
     *   Select Country – This will be a multi-selection dropdown. Single or multiple countries can be selected. The ad will show to users of the selected country only.
     *   Select Gender – This will be a single or multiple selection. The dropdown will contain the following options:
     *   Any
     *   Male
     *   Female
     *   Not Specified
     *   Clickable / Swappable – Radio buttons: For custom ads, admin will have the option to either make the ads clickable or swappable
     *   Clickable – Clickable means if user tabs, they will be taken a URL. If admin chooses clickable, a text field will appear and admin will enter a URL.
     *   Swappable – Swappable means users will be able to mark favorite an ad from the 2 shown.
     *   Draft / Publish – When creating a post, there will be 2 buttons
     *   Draft – Draft means an ad is created but not made live and not visible to users.
     *   Publish – Publish means an ad is created and made live and visible to users.
     *   Premium option
     *   Specific ads that will be shown together e.g., 2 coke adverts, so they can get feedback on which ad users prefer
     */
    public function createAdds(createAdds $request)
    {
        $countries      =   $request->input('countries');
        $topics         =   $request->input('topic_ids');

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

        //check if country id exist
        $countriesModel         =   new country();
        $checkIfCountriesExist  =   $countriesModel->checkIfCountriesExist($countries);
        if (!$checkIfCountriesExist) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Invalid country id'
            ], 400);
        }

        $advertisementCategory  =   $request->input('advertisement_category') == 'basic' ? Constants::CUSTOM_ADDS_CATEGORY_STATUS_BASIC :  Constants::CUSTOM_ADDS_CATEGORY_STATUS_PREMIUM;
        if ($advertisementCategory == Constants::CUSTOM_ADDS_CATEGORY_STATUS_PREMIUM) {

            if ($request->hasFile('primary_file') && $request->hasFile('secondary_file')) {

                $primaryFile    =   $request->file('primary_file');
                $primaryFileUrl        =   $request->input('primary_url');
                DB::beginTransaction();
                $customAddsPrimary  =   $this->customAddsHelper($request, $primaryFileUrl, $primaryFile);

                if ($customAddsPrimary['status'] == 200) {
                    $secondaryFile   =   $request->file('secondary_file');
                    $secondaryFileUrl      =   $request->input('secondary_url');
                    $customAddsSecondary  =   $this->customAddsHelper($request, $secondaryFileUrl, $secondaryFile);

                    if ($customAddsSecondary['status'] == 200) {

                        $advertisementMapperData['custom_add_id']           =   $customAddsPrimary['custom_add_id'];
                        $advertisementMapperData['other_custom_add_id']     =   $customAddsSecondary['custom_add_id'];
                        $advertisementMapperData['type']                    =   Constants::CUSTOM_ADDS_CATEGORY_STATUS_PREMIUM;
                        $advertisementMapperData['updated_at']              =   $request->input('current_time');
                        $advertisementMapperData['created_at']              =   $request->input('current_time');

                        DB::commit();

                        $data   =   advertisementMapper::create($advertisementMapperData);
                        if ($data) {
                            return response()->json([
                                'status'    =>  $customAddsSecondary['status'],
                                'success'   =>  $customAddsSecondary['success'],
                            ]);
                        }

                        return response()->json(
                            [
                                'status'    =>  500,
                                'success'   =>  false,
                                'error'     =>  [
                                    'message'   =>  'Error Creating Custom Adds'
                                ]
                            ]
                        );
                    } else {

                        return response()->json($customAddsSecondary, $customAddsPrimary['status']);
                    }
                } else {

                    return response()->json($customAddsPrimary, $customAddsPrimary['status']);
                }
            } else {

                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Please provide both primary and secondary custom-ads media'
                ], 400);
            }
        } else {

            $primaryFile = $request->file('primary_file');
            $primaryFileUrl    = $request->input('primary_url');
            DB::beginTransaction();
            $customAddsPrimary = $this->customAddsHelper($request, $primaryFileUrl, $primaryFile);
            if ($customAddsPrimary['status'] == 200) {

                $advertisementMapperData['custom_add_id']           =   $customAddsPrimary['custom_add_id'];
                $advertisementMapperData['other_custom_add_id']     =   null;
                $advertisementMapperData['type']                    =   Constants::CUSTOM_ADDS_CATEGORY_STATUS_BASIC;
                $advertisementMapperData['created_at']              =   $request->input('current_time');

                $data   =   advertisementMapper::create($advertisementMapperData);
                if ($data) {
                    DB::commit();
                    return response()->json([
                        'status'    =>  $customAddsPrimary['status'],
                        'success'   =>  $customAddsPrimary['success'],
                    ]);
                }

                return response()->json(
                    [
                        'status'    =>  500,
                        'success'   =>  false,
                        'error'     =>  [
                            'message'   =>  'Error Creating Custom Adds'
                        ]
                    ]
                );
            } else {

                return response()->json($customAddsPrimary, $customAddsPrimary['status']);
            }
        }
    }

    public function customAddsHelper($request, $fileUrl, $file)
    {
        $type           =   $request->input('type');
        $currentTime    =   $request->input('current_time');
        $countries      =   $request->input('countries');
        $topics         =   $request->input('topic_ids');
        $genders        =   $request->input('gender');
        $status         =   $request->input('save_as') == 'publish' ? Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE : Constants::CUSTOM_ADDS_STATUS_TYPE_DRAFT;
        $media_action   =   $request->input('action') == 'clickable' ? Constants::CUSTOM_ADDS_ACTION_CLICKABLE_INT : Constants::CUSTOM_ADDS_ACTION_SWAPABLE_INT;

        if ($type == 'video') {
            if ($request->hasFile('file')) {
                // $file = $request->file('file');

                $customAddsModel = new CustomAdds();

                $CustomAddsVideoPath = $customAddsModel->uploadCustomAddsVideo($file);
                if ($CustomAddsVideoPath) {
                    DB::beginTransaction();
                    //add file
                    $customAddsData['url']                      = $fileUrl;
                    $customAddsData['file_url']                 = $CustomAddsVideoPath['video_url'];
                    $customAddsData['thumb_url']                = $CustomAddsVideoPath['video_thumb'];
                    $customAddsData['media_type']               = Constants::CUSTOM_ADDS_MEDIA_TYPE_VIDEO_INT;
                    $customAddsData['action']                   = $media_action;
                    $customAddsData['status']                   = $status;
                    $customAddsData['created_at']               = $currentTime;

                    // $postModel  =   new CustomAdds();
                    $CreatedAd   = $customAddsModel->createCustomAd($customAddsData);
                    if ($CreatedAd) {
                        $customAddTopics        =   $customAddsModel->createCustomAdTopics($CreatedAd, $topics);
                        $customAddsGenderModel  =   new CustomAddsGender();
                        $customAddsGender       =   $customAddsGenderModel->createCustomAddsGenders($CreatedAd, $genders);
                        $customAdsCountries     =   $customAddsModel->createUserCountries($CreatedAd, $countries);
                        DB::commit();
                        return ['status'  => '200', 'success' => array('message' => 'Custom Ad Created.'), 'custom_add_id' => $CreatedAd->id];
                    } else {
                        return [
                            'status'    =>  500,
                            'success'   =>  false,
                            'error'     =>  [
                                'message'   =>  'Server Error'
                            ]
                        ];
                    }
                } else {
                    return [
                        'status'    =>  400,
                        'success'    =>  false,
                        'error'   =>  'Please upload video less than or equal 15 seconds and 50mb'
                    ];
                }
            } else {
                return ['status' => '400', 'error' => array('message' => 'Choose Video File.')];
            }
        } else {
            //storing adverts image
            if ($file) {
                // dd($file);

                $customAddsModel = new CustomAdds();
                $CustomAddsVideoPath = $customAddsModel->uploadCustomAddsImage($file);
                DB::beginTransaction();
                $customAddsData['url']                      = $fileUrl;
                $customAddsData['file_url']                 = $CustomAddsVideoPath['image_path'];
                $customAddsData['thumb_url']                = $CustomAddsVideoPath['thumb_path'];
                $customAddsData['media_type']               = Constants::CUSTOM_ADDS_MEDIA_TYPE_IMAGE_INT;
                $customAddsData['action']                   = $media_action;
                $customAddsData['status']                   = $status;
                $customAddsData['created_at']               = $currentTime;

                $CreatedAd   = $customAddsModel->createCustomAd($customAddsData);
                if ($CreatedAd) {

                    $customAddTopics        =   $customAddsModel->createCustomAdTopics($CreatedAd, $topics);
                    $customAddsGenderModel  =   new customAddsGender();
                    $customAddsGender       =   $customAddsGenderModel->createCustomAddsGenders($CreatedAd, $genders);
                    $customAdsCountries     =   $customAddsModel->createUserCountries($CreatedAd, $countries);
                    DB::commit();
                    return ['status'  => '200', 'success' => array('message' => 'Custom Ad Created.'), 'custom_add_id' => $CreatedAd->id];
                } else {
                    return [
                        'status'    =>  500,
                        'success'   =>  false,
                        'error'     =>  [
                            'message'   =>  'Server Error'
                        ]
                    ];
                }
            }
        }
    }

    /**
     * The custom ads module will allow admin to view all the existing (already created) ads. All ads will have 3 statuses:
     *   Draft -- Ad is created but not published. Not visible to users.
     *   Live – Ad is created and published. Visible to users.
     *   Expired – Ad is no longer visible to users.
     *   The screen will display all the created ads in a list view.
     *   Clicking on any ad will open a detail view of the ad.
     *   There will be certain action button in each row for ad status change and edit each ad.

     *Acceptance Criteria
     *   Admin should be able to view all the created ads in the admin panel.
     *   There must be 2 ad post created or enabled at a time which will be displayed over the application.
     *   Admin will be able to edit and delete ads.
     *   All ads should have the 3 statuses:
     *   Draft
     *   Live
     *   Expired

     *   Expired post - Can be Live by tapping on the button.
     *   Draft post - Can be Live by tapping on the button.
     *   Live post - Can be Expired by tapping on the button.

     */
    public function getCustomAdds(Request $request)
    {

        $like                   =   $request->input('like');
        $filter                 =   $request->input('filter');
        $limit                  =   $request->input('limit') ? $request->input('limit') : 10;
        $offset                 =   $request->input('offset') ? $request->input('offset') : 0;
        $filter                 =   $this->getCustomAddsStatusInteger($filter);
        $addvertMapperModel     =   new advertisementMapper();
        $records                =   $addvertMapperModel->getCustomAdds($like, $filter);
        $recordsForCount        =   $records;
        $count                  =   $recordsForCount->count();
        $recordsWithoutCount    =   $records->take($limit)->skip($offset)->get();

        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data Fetched Successfully',
            'count'     =>  $count,
            'data'      =>  $recordsWithoutCount
        ]);
    }

    /*
    * This method use for get custom adds status integer.
    */
    public function getCustomAddsStatusInteger($value)
    {
        $status = '';
        switch ($value) {
            case 'live':
                $status = [1];
                break;
            case 'in_active':
                $status = [2];
                break;
            case 'draft':
                $status = [3];
                break;
            case 'expired':
                $status = [4];
                break;
            default:
                $status = [1, 2, 3, 4];
                break;
        }
        return $status;
    }

    /**
     *  By tapping on the edit icon from list view of custom ads admin will be able to edit the ad image, country, url, clickable/swappable, gender.
     */
    public function updateCustomAdds($customAdds, $file, $fileUrl, $request)
    {
        $url            =   $fileUrl;
        $action         =   $request->input('action') == 'clickable' ? Constants::CUSTOM_ADDS_ACTION_CLICKABLE_INT : Constants::CUSTOM_ADDS_ACTION_SWAPABLE_INT;
        $type           =   $request->input('type');
        $currentTime    =   $request->input('current_time');
        $countries      =   $request->input('countries');
        $genders         =   $request->input('gender');
        $customAddsModel = new CustomAdds();

        //check if country id exist
        $countriesModel         =   new country();
        $checkIfCountriesExist  =   $countriesModel->checkIfCountriesExist($countries);
        if (!$checkIfCountriesExist) {
            return [
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  'Invalid country id'
            ];
        }

        if ($request->filled('type')) {
            if ($file) {
                $imageUrl    =  $customAdds->file_url;
                $thumbUrl    =  $customAdds->thumb_url;
                $imagePath = explode('.com/', $imageUrl);
                $thumbPath = explode('.com/', $thumbUrl);
                //if image delete it first
                $fileExists =   Storage::disk('s3')->exists($imagePath[1]);
                if ($fileExists) {
                    $deleteImage    =   Storage::disk('s3')->delete($imagePath[1]);
                    $thumbFileExists     =   Storage::disk('s3')->exists($thumbPath[1]);
                    if ($thumbFileExists) {
                        $deleteImage    =   Storage::disk('s3')->delete($thumbPath[1]);
                    }
                }



                if ($type == 'video') {
                    if ($file) {
                        // $file = $request->file('file');

                        $CustomAddsVideoPath = $customAddsModel->uploadCustomAddsVideo($file);
                        if ($CustomAddsVideoPath) {
                            DB::beginTransaction();
                            //add file
                            $customAddsData['url']                      = $url;
                            $customAddsData['file_url']                 = $CustomAddsVideoPath['video_url'];
                            $customAddsData['thumb_url']                = $CustomAddsVideoPath['video_thumb'];
                            $customAddsData['media_type']               = Constants::CUSTOM_ADDS_MEDIA_TYPE_VIDEO_INT;
                            $customAddsData['action']                   = $action;
                            $customAddsData['status']                   = Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE;
                            $customAddsData['created_at']               = $currentTime;

                            // $postModel  =   new CustomAdds();
                            $updatedAdd   = $customAddsModel->updateCustomAd($customAdds->id, $customAddsData);
                            if ($updatedAdd) {
                                return ['status'  => '200', 'success' => array('message' => 'Custom Ad Updated.')];
                                $customAddsGenderModel  =   new CustomAddsGender();
                                $customAddsGender       =   $customAddsGenderModel->updateCustomAddsGender($updatedAdd, $genders);
                                $customAdsCountries     =   $customAddsModel->createUserCountries($updatedAdd, $countries);
                                DB::commit();
                            } else {
                                return [
                                    'status'    =>  500,
                                    'success'   =>  false,
                                    'error'     =>  [
                                        'message'   =>  'Server Error'
                                    ]
                                ];
                            }
                        } else {
                            return [
                                'status'    =>  400,
                                'success'    =>  false,
                                'error'   =>  'Please upload video less than or equal 15 seconds and 50mb'
                            ];
                        }
                    } else {
                        return ['status' => '400', 'error' => array('message' => 'Choose Video File.')];
                    }
                } else {
                    //storing adverts image
                    if ($file) {
                        // $file = $request->file('file');


                        $CustomAddsVideoPath = $customAddsModel->uploadCustomAddsImage($file);
                        $customAddsData['url']                      = $url;
                        $customAddsData['file_url']                 = $CustomAddsVideoPath['image_path'];
                        $customAddsData['thumb_url']                = $CustomAddsVideoPath['thumb_path'];
                        $customAddsData['media_type']               = Constants::CUSTOM_ADDS_MEDIA_TYPE_IMAGE_INT;
                        $customAddsData['action']                   = $action;
                        $customAddsData['status']                   = Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE;
                        $customAddsData['created_at']               = $currentTime;

                        $updatedAdd   = $customAddsModel->updateCustomAd($customAdds->id, $customAddsData);
                        if ($updatedAdd) {
                            $customAddsGenderModel  =   new CustomAddsGender();
                            $customAddsGender       =   $customAddsGenderModel->updateCustomAddsGender($updatedAdd, $genders);
                            $customAdsCountries     =   $customAddsModel->createUserCountries($updatedAdd, $countries);
                            return ['status'  => '200', 'success' => array('message' => 'Custom Ad updated.')];
                        } else {
                            return [
                                'status'    =>  500,
                                'success'   =>  false,
                                'error'     =>  [
                                    'message'   =>  'Server Error'
                                ]
                            ];
                        }
                    }
                }
            }
        }

        //add file
        $customAddsData['url']                      = $url;
        $customAddsData['action']                   = $action;
        $customAddsData['created_at']               = $currentTime;

        // $postModel  =   new CustomAdds();
        $updatedAdd   = $customAddsModel->updateCustomAd($customAdds->id, $customAddsData);
        if ($updatedAdd) {
            return ['status'  => '200', 'success' => array('message' => 'Custom Ad Updated.')];
            $customAddsGenderModel  =   new CustomAddsGender();
            $customAddsGender       =   $customAddsGenderModel->updateCustomAddsGender($updatedAdd, $genders);
            $customAdsCountries     =   $customAddsModel->createUserCountries($updatedAdd, $countries);
        } else {
            return [
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   =>  'Server Error'
                ]
            ];
        }
    }

    /**
     *  this function decides weither to update basic or premium advertisement
     */
    public function updateCustomAdMapper(advertisementMapper $advertisementMapper,  updateCustomAdds $request)
    {
        try {
            $primaryFile    =   $request->file('primary_file');
            $primaryFileUrl =   $request->input('primary_url');

            if ($advertisementMapper->type == Constants::CUSTOM_ADDS_CATEGORY_TYPE_PREMIUM) {

                $mappedAdvert   =   $advertisementMapper->load(['custom_add_primary', 'custom_add_secondary']);
                DB::beginTransaction();
                $primaryAddUpdate   = $this->updateCustomAdds($mappedAdvert->custom_add_primary, $primaryFile, $primaryFileUrl, $request);
                if ($primaryAddUpdate['status'] == 200) {
                    $secondaryFile          =   $request->file('secondary_file');
                    $secondaryFileUrl       =   $request->input('secondary_url');

                    $secondaryAddUpdate =   $this->updateCustomAdds($mappedAdvert->custom_add_secondary, $secondaryFile, $secondaryFileUrl, $request);
                    if ($secondaryAddUpdate['status'] == 200) {
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                    return response()->json($secondaryAddUpdate, $secondaryAddUpdate['status']);
                } else {
                    return response()->json($primaryAddUpdate, $primaryAddUpdate['status']);
                }
            } else {
                $mappedAdvert   =   $advertisementMapper->load('custom_add_primary');
                DB::beginTransaction();
                $primaryAddUpdate   = $this->updateCustomAdds($mappedAdvert->custom_add_primary, $primaryFile, $primaryFileUrl, $request);
                if ($primaryAddUpdate['status'] == 200) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
                return response()->json($primaryAddUpdate, $primaryAddUpdate['status']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => '500', 'error' => $e->getMessage() . $e->getLine() . $e->getFile() . $e], 500);
        }
    }

    /**
     *
     *   As an admin, I should be able to delete the custom ads
     *   By tapping on the delete icon from list view of custom ads admin will be able to delete custom ads.
     */
    public function deleteCustomAdds(advertisementMapper $advertisementMapper)
    {
        try {
            DB::beginTransaction();

            $record = $advertisementMapper->delete();

            if ($record) {

                DB::commit();

                return response()->json([
                    'status'    =>  200,
                    'success'   =>  true,
                    'message'   =>  'Custom Ad deleted successfully'
                ]);
            }

            return response()->json([
                'status' => 400,
                'success' => false,
                'error' =>  'Error deleting custom ad'
            ], 400);
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
     *The ads will appear very similar to the home screen view with 2 posts shown. Instead of 2 posts, the same post placeholders will be used to display ads; meaning there will be 2 fixed size ads shown on home screen. (Either Admob & Admob OR custom & custom). However, the screen will say “adverts” to highlight if these are sponsored posts.
     *   Ads can be repeated meaning they can appear to same user more than once.
     *   Note: On both type of ads, some sort of design variation or some custom text like (premium, ads, sponsored etc.) should appear.
     *   Both ads will appear in regular intervals example:
     *   -        After 10 post – Admob Vs Admob -After next iteration of 6 to 10 posts (depends on the parameters set in config items) – Custom Vs Custom.
     *   -        Adverts should be labeled categorically.
     *   -  But initially, there may not be a lot of custom ad content. There would be
     *   5-1 ratio for the Ads, for e.g after every 10 post there will be Admob vs Admob, this will be done in 5 iterations,  and the 6th will be custom vs custom.
     *
     *   Admob Vs Admob – There will be 2 ads displayed in the post placeholders. They will be generated from Admob. (Whatever controller Admob provides, it will be limited to those controllers in terms of interaction)
     *
     *   Custom Vs Custom ads – These kinds of ads are generated from the admin panel. They will appear in the same post placeholders. Users can choose anyone as favorite. (Ad’s favorite will be a 1-time deal and may not be part of regular post favorites).
     *
     *   Ads can be:
     *   1)        Clickable – Clickable means if user tabs, they will be taken a URL.
     *   2)        Swappable – Swappable means users will be able to mark favorite an ad from the 2 shown.
     *
     *
     *   Both ads availability will be controlled from config section.
     *   If no ads are available then posts will be displayed over the screen.
     *   Skip option is mandatory for ads, after tapping on the skip option user will be able to see regular post on their screen.


     */
    public function getUserCustomAds(Request $request)
    {
        $user               =   Auth::user();
        $customAddsModel    =   new CustomAdds();
        if ($request->filled('ad_like_id') && $request->filled('ad_dislike_id')) {
            $reactionData['user_id']            =    $user->id;
            $reactionData['advert_like_id']     =    $request->input('ad_like_id');
            $reactionData['advert_dislike_id']  =    $request->input('ad_dislike_id');
            $reactionData['created_at']         =    $request->input('current_time');
            $advertReactionModel                =   new AdvertisementReaction();
            $advertReactionModel->addAdvertReaction($reactionData);
        }
        $customAds          =   $customAddsModel->getUserAds($user);
        if (count($customAds['alternative_post']) == 2) {

            CustomAdds::whereIn('id', [$customAds['alternative_post'][0]->id, $customAds['alternative_post'][1]->id])
                ->increment('views');
        }
        return response()->json([
            'status'    =>  200,
            'success'   =>  true,
            'message'   =>  'Data Fetched Successfully',
            'data'      =>  $customAds
        ]);
    }

    public function incrementClicks(CustomAdds $customAdds)
    {
        try {

            $flag=$customAdds->increment('clicks');
            if($flag)
            {
                return response()->json([
                    'status'    =>  200,
                    'success'   =>  true,
                    'message'   =>  'Clicks added successfully'
                ]);
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

    public function toggleAd(CustomAdds $customAd)
    {

        if ($customAd->status == Constants::CUSTOM_ADDS_STATUS_DRAFT || $customAd->status == Constants::CUSTOM_ADDS_STATUS_EXPIRED) {
            $changed_status = Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE;
        } else {
            $changed_status = Constants::CUSTOM_ADDS_STATUS_TYPE_EXPIRED;
        }
        $data['status']         =   $changed_status;
        $data['timestamps']     =   false;
        $adModel                =   new CustomAdds();
        $updateAd               =   $adModel->updateCustomAd($customAd->id, $data);
        if ($updateAd) {
            return response()->json([
                'status'    =>  200,
                'success'   =>  'true',
                'message'   =>  'Ad updated successfuly'
            ]);
        }

        return response()->json([
            'status'    =>  400,
            'success'   =>  'false',
            'error'     =>  'Ad update failed'
        ]);
    }
}
