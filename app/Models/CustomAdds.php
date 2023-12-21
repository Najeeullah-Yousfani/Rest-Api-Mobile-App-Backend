<?php

namespace App\Models;

use App\config\Constants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use FFMpeg;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class CustomAdds extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'def_topic_id',
        'country_id',
        'url',
        'file_url',
        'thumb_url',
        'media_type',
        'clicks',
        'action',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    protected $table = 'custom_ads';

    public function getStatusAttribute($value)
    {
        $status = '';
        switch ($value) {

            case 1:
                $status = Constants::CUSTOM_ADDS_STATUS_ACTIVE;
                break;
            case 2:
                $status = Constants::CUSTOM_ADDS_STATUS_IN_ACTIVE;
                break;
            case 3:
                $status = Constants::CUSTOM_ADDS_STATUS_DRAFT;
                break;
            case 4:
                $status = Constants::CUSTOM_ADDS_STATUS_EXPIRED;
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }

    public function getMediaTypeAttribute($value)
    {
        $status = '';
        switch ($value) {

            case 1:
                $status = 'image';
                break;
            case 2:
                $status = 'video';
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }

    public function getActionAttribute($value)
    {
        $status = '';
        switch ($value) {

            case 1:
                $status = 'clickable';
                break;
            case 2:
                $status = 'swapable';
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }

    public function getFileUrlAttribute($value)
    {
        $appEnviro = env('APP_ENV');
        $basePath = '';
        switch ($appEnviro) {
            case 'local':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'production':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'staging':
                $basePath = env('AWS_URL') . $value;
                break;

            default:
                $basePath = '';
                break;
        }
        return $basePath;
    }

    public function getThumbUrlAttribute($value)
    {
        $appEnviro = env('APP_ENV');
        $basePath = '';
        switch ($appEnviro) {
            case 'local':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'production':
                $basePath = env('AWS_URL') . $value;
                break;

            case 'staging':
                $basePath = env('AWS_URL') . $value;
                break;

            default:
                $basePath = '';
                break;
        }
        return $basePath;
    }

    public function customAdsTopics()
    {
        return $this->belongsToMany(Topic::class, 'custom_ads_topics', 'custom_ads_id', 'def_topic_id')->withTimestamps();
    }

    public function customAdsCountries()
    {
        return $this->belongsToMany(country::class, 'custom_ads_countries', 'custom_ads_id', 'country_id')->withTimestamps();
    }

    public function addLike()
    {
        return $this->hasMany(AdvertisementReaction::class, 'advert_like_id');
    }

    public function addDislike()
    {
        return $this->hasMany(AdvertisementReaction::class, 'advert_dislike_id');
    }

    public function customAdsGender()
    {
        return $this->hasMany(CustomAddsGender::class,'custom_ads_id');
    }

    /**
     * Custom Ads : Create Custom Ads
     */
    public function createCustomAd($data)
    {
        $user   =   CustomAdds::create($data);
        return $user ? $user : [];
    }

    public function updateCustomAd($id, $data)
    {
        $customAdd =  CustomAdds::find($id);
        $customAdd->update($data);
        $customAdd->save();
        return $customAdd ? $customAdd : array();
    }

    public function uploadCustomAddsVideo($file)
    {
        // $user      = Auth::user();
        $image     = $file;
        $extension = $image->getClientOriginalExtension();
        $ImgName   = $image->getClientOriginalName();
        $ImgName   = str_replace(" ", "_", $ImgName);
        $time      = time();
        $fileNameWithoutEx = pathinfo($ImgName, PATHINFO_FILENAME);
        $imageName = $fileNameWithoutEx . "_" . $time . "." . $extension;
        $basePath      = "admin/custom_ads";
        $destinationPath = public_path($basePath);
        if (!file_exists($destinationPath)) {
            //create folder
            mkdir(public_path($basePath), 0777, true);
        }
        $basePath      = "admin/custom_ads";
        $destinationPath = $basePath . $imageName;
        // $destinationPath = base_path('public/user_images/user_' . $userId . '');
        $pathForVideoThumbnails = public_path($basePath) . '/thumb_' . $fileNameWithoutEx . "_" . $time . "." . 'jpg';
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            // 'ffmpeg.binaries'  => 'C:\ffmpeg\bin\ffmpeg.exe',
            // 'ffprobe.binaries' => 'C:\ffmpeg\bin\ffprobe.exe',
            'timeout'          => 36000000000000000000000, // The timeout for the underlying process
            'ffmpeg.threads'   => 16,   // The number of threads that FFMpeg should use
        ]);
        $video = $ffmpeg->open($image);
        $video
            ->filters()
            ->resize(new FFMpeg\Coordinate\Dimension(320, 240))
            ->synchronize();
        // $video
        //     ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
        //     ->save('frame.jpg');
        // $video = $ffmpeg->open($image);
        $ffprobe = ffmpeg\FFProbe::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            // 'ffmpeg.binaries'  => 'C:\ffmpeg\bin\ffmpeg.exe',
            // 'ffprobe.binaries' => 'C:\ffmpeg\bin\ffprobe.exe',
            'timeout'          => 36000000000000000000000, // The timeout for the underlying process
            'ffmpeg.threads'   => 16,   // The number of threads that FFMpeg should use
        ]);
        $duration = $ffprobe
            ->format($file) // extracts file informations
            ->get('duration');

        if ($duration > 15.99) {
            return false;
        }

        $secToCut   =   $duration / 2;

        $video
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($secToCut))
            ->save($pathForVideoThumbnails);


        // MOV video file extension convert into mp4
        $image->move(public_path($basePath), $fileNameWithoutEx . "_" . $time . "." . $extension);
        $pathFormp4File = public_path($basePath) . '/' . $fileNameWithoutEx . "_" . $time . "." . $extension;
        $convertedFileName = public_path($basePath) . '/' . $fileNameWithoutEx . "_" . $time;

        \shell_exec("ffmpeg -i $pathFormp4File $convertedFileName.mp4");
        $videoName = 'admin/custom_ads/' . $fileNameWithoutEx . "_" . $time . ".mp4";
        $imageName = 'admin/custom_ads/thumb_' . $fileNameWithoutEx . "_" . $time . ".jpg";
        if (env('APP_ENV') == "production") {

            $thumbS3Storage = Storage::disk('s3')->put($imageName, \fopen($pathForVideoThumbnails, 'r+'));
            $imageS3Storage = Storage::disk('s3')->put($videoName, file_get_contents($convertedFileName . ".mp4"));

            //if stored to s3 delete from local directory
            if ($thumbS3Storage) {
                unlink($pathForVideoThumbnails);
                if ($imageS3Storage) {
                    unlink($pathFormp4File);
                }
            }
        }
        $file_paths = [
            'video_thumb'   =>  $imageName,
            'video_url'     =>  $videoName
        ];
        return $file_paths;
    }
    public function uploadCustomAddsImage($file)
    {
        $image          = $file;
        $extension      = $image->getClientOriginalExtension();
        $imageName      = $image->getClientOriginalName();
        $imageName      = str_replace(' ', '_', $imageName);
        $fileNameWithoutExt = pathinfo($imageName, PATHINFO_FILENAME);
        $destinationPath = base_path('public/admin/custom_ads');
        if (!file_exists($destinationPath)) {
            //create folder
            mkdir($destinationPath, 0777, true);
        }
        $time           = time();
        $imageUrl       = $fileNameWithoutExt . '_' . $time . '.' . $extension;
        $image->move($destinationPath, $imageUrl);

        //generating thumbnail
        $image          = ImageManagerStatic::make($destinationPath . '/' . $imageUrl)->resize('550', '340');
        $image->orientate();
        $thumbImageUrl  = '/thumb_' . $fileNameWithoutExt . '_' . $time . '-' . '550x340' . '.' . $extension;
        $image->save($destinationPath . $thumbImageUrl);

        $urlImage = $destinationPath . '/' . $imageUrl;
        $urlThumb = $destinationPath . $thumbImageUrl;

        //s3 Configurations
        $imagePath = 'admin/custom_ads/' . $imageUrl;
        $thumbPath = 'admin/custom_ads' . $thumbImageUrl;
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

        $file_paths = [
            'image_path'        =>  $imagePath,
            'thumb_path'        =>  $thumbPath
        ];
        return $file_paths;
    }

    public function createCustomAdTopics($customAdd, $topics)
    {
        $customAdd->customAdsTopics()->sync($topics);
        $customAddTopics    =   $customAdd->load('customAdsTopics');
        return $customAddTopics ? $customAddTopics : [];
    }

    /**
     * Create custom ads country from countries
     */
    public function createUserCountries($customAdd, $countries)
    {
        $customAdd->customAdsCountries()->sync($countries);
        $createCustomAdCountries = $customAdd->load('customAdsCountries');
        return $createCustomAdCountries ? $createCustomAdCountries : [];
    }

    public function getCustomAdds($like, $filter)
    {
        return $records    =   CustomAdds::with(['customAdsTopics', 'customAdsCountries'])
            ->where(function ($query) use ($filter, $like) {
                if ($filter) {
                    $query->whereIn('status', $filter);
                }

                if ($like) {
                    $query->where('id', 'like', '%' . $like . '%');
                }
            });
    }

    /**
     * this function return two advertisement that are active at a time
     */
    public function getUserAds($user)
    {
        // dd($user);
        $randomSelector     =  rand(1, 2);
        $customAds['favourite_post']    =   null;
        // $randomSelector = 2;
        if ($randomSelector == 1) {
            $premiumAds         =   $this->premiumAdvertisement($user);
            $premiumAdsCount    =   $premiumAds;
            $premiumAdsCount    =   $premiumAdsCount->count();
            $premiumAds         =   $premiumAds->first();
            if ($premiumAdsCount) {
                $customAds['alternative_post']  =   [$premiumAds->custom_add_primary, $premiumAds->custom_add_secondary];
            } else {
                $basicAdverts         =   $this->basicAdvertisement($user);
                $basicAdvertsCount    =   $basicAdverts;
                $basicAdvertsCount    =   $basicAdvertsCount->count();
                $basicAdverts         =   $basicAdverts->get();
                if ($basicAdvertsCount == 2)
                {
                    $customAds['alternative_post']  =   [$basicAdverts[0]->custom_add_primary, $basicAdverts[1]->custom_add_primary];
                }
                else
                {
                    $customAds['alternative_post'] =[];
                }

            }
            return $customAds;
        } else {
            $basicAdverts         =   $this->basicAdvertisement($user);
            $basicAdvertsCount    =   $basicAdverts;
            $basicAdvertsCount    =   $basicAdvertsCount->count();
            $basicAdverts         =   $basicAdverts->get();
            if ($basicAdvertsCount == 2) {
                $customAds['alternative_post']  =   [$basicAdverts[0]->custom_add_primary, $basicAdverts[1]->custom_add_primary];
            } else {
                $premiumAds         =   $this->premiumAdvertisement($user)->first();
                if ($premiumAds) {
                    $customAds['alternative_post']  =   [$premiumAds->custom_add_primary, $premiumAds->custom_add_secondary];
                }else
                {
                    $customAds['alternative_post']  =   [];
                }
            }
        }
        return $customAds;
    }

    /**
     * helper function to get basic advertiesments
     */
    public function basicAdvertisement($user)
    {
        $query        =   advertisementMapper::whereHas('custom_add_primary', function ($query) use ($user) {
            $query->leftJoin('custom_ads_gender', 'custom_ads_gender.custom_ads_id', 'custom_ads.id')
                ->where('status', Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE)
                ->whereHas('customAdsCountries', function ($query) use ($user) {
                    $query->select('countries.id as country_id')->where('country_id', $user->country_id);
                })
                ->where('custom_ads_gender.gender', $user->gender);
        })
            ->where('type', Constants::CUSTOM_ADDS_CATEGORY_STATUS_BASIC)->inRandomOrder()->take(2);
        return $query;
    }

    /**
     * helper function to get premium advertisements
     */
    public function premiumAdvertisement($user)
    {
        $query = advertisementMapper::whereHas('custom_add_primary', function ($query) use ($user) {
            $query->leftJoin('custom_ads_gender', 'custom_ads_gender.custom_ads_id', 'custom_ads.id')
                ->where('status', Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE)
                ->whereHas('customAdsCountries', function ($query) use ($user) {
                    $query->select('countries.id as country_id')->where('country_id', $user->country_id);
                })
                ->where('custom_ads_gender.gender', $user->gender);
        })
            ->whereHas('custom_add_secondary', function ($query) use ($user) {
                $query->leftJoin('custom_ads_gender', 'custom_ads_gender.custom_ads_id', 'custom_ads.id')
                    ->where('status', Constants::CUSTOM_ADDS_STATUS_TYPE_ACTIVE)
                    ->whereHas('customAdsCountries', function ($query) use ($user) {
                        $query->select('countries.id as country_id')->where('country_id', $user->country_id);
                    })
                    ->where('custom_ads_gender.gender', $user->gender);
            })
            ->where('type', Constants::CUSTOM_ADDS_CATEGORY_STATUS_PREMIUM)->inRandomOrder()->take(1);

        return $query;
    }
}
