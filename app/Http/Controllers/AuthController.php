<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\changePassword;
use App\Http\Requests\changeUserPassword;
use App\Http\Requests\resetPassword;
use App\Http\Requests\userDetails;
use App\Http\Requests\userlogin;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Requests\userSignUpRequest;
use App\Http\Requests\userVerification;
use App\Models\AccessToken;
use App\Models\AdminConfig;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopic;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
// use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpParser\Node\Expr\AssignOp\Concat;
use PHPUnit\TextUI\XmlConfiguration\Constant;

use function PHPUnit\Framework\fileExists;

class AuthController extends Controller
{
    /**
     * Creating a new user
     *
     */
    public function createUser(userSignUpRequest $request)
    {
        try {
            $userModel =  new User();
            $input = $request->all();
            $email = $input['email'];
            $digits = 4;
            $verificationCode       = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
            $verificationCodeExpiry = Carbon::parse($input['current_time'])->addDays(7);
            $verificationCodeExpiry = $verificationCodeExpiry->toArray();
            $createAccountData['role_id']               =   Constants::ROLE_USER;
            $createAccountData['email']                 =   $email;
            $createAccountData['password']              =   bcrypt($input['password']);
            $createAccountData['platform']              =   $input['platform'];
            $createAccountData['status']                =   Constants::USER_STATUS_UNVERIFIED_INT;
            $createAccountData['verification_code']     =   $verificationCode;
            $createAccountData['verify_code_expiry']    =   $verificationCodeExpiry['formatted'];
            $createAccountData['created_at']            =   $input['current_time'];

            DB::beginTransaction();
            $createdUser = $userModel->createUser($createAccountData);
            if (!$createdUser) {
                return response()->json([
                    'status'    =>  400,
                    'success'    =>  false,
                    'error'   =>  'Error creating user'
                ], 400);
            }
            DB::commit();
            $check = Mail::raw("FindUr App account verification code is: $verificationCode", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Account Verification Code - FindUr App')->from(env('MAIL_FROM'));
            });


            return response()->json([
                'status'    =>  200,
                'success'    =>  true,
                'message'   =>  'Account created successfully',
                'data'      =>  $createdUser
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage()
                ]
            ],500);
        }
    }


    /**
     * Creating user details
     */
    public function createUserDetails(userDetails $request)
    {
        try {
            $userModel          = new User();
            $userId             = $request->input('user_id');
            $user               = User::find($userId);
            $input              = $request->all();
            //validating user
            // $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Invalid user id'
                ], 400);
            }

            //storing profile image
            if ($request->hasFile('profile_image')) {
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

                $createUserDetails['profile_image']         = $imagePath;
                $createUserDetails['thumb_image']           = $thumbPath;
            }

            //checking admin auto-login feature
            $adminSettingModel  =   new AdminConfig();
            $config = $adminSettingModel->getSpecificConfig(Constants::AUTO_LOGIN);
            $status = ($config->status == Constants::STATUS_ACTIVE) ?  Constants::USER_STATUS_PROFILE_INCOMPLETE_INT : Constants::USER_STATUS_PERMIT_PROFILE_INCOMPLETE_INT;


            //storing further user details
            $createUserDetails['username']                  =   $input['username'];
            $createUserDetails['country_id']                =   $input['country_id'];
            $createUserDetails['city']                      =   $input['city'];
            $createUserDetails['gender']                    =   $input['gender'];
            $createUserDetails['dob']                       =   $input['dob'];
            $createUserDetails['bio_details']               =   $input['bio_details'];
            $createUserDetails['gender']                    =   $input['gender'];
            $createUserDetails['status']                    =   $status;
            $createUserDetails['created_at']                =   $input['current_time'];
            $createUserDetails['updated_at']                =   $input['current_time'];
            DB::beginTransaction();
            $user   =   $userModel->updateUser($userId, $createUserDetails);
            if (!$user) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Error creating user details'
                ]);
            }
            $accessTokenModel   =   new AccessToken();
            $userId             =   $user->id;
            $userStatus         =   $user->status;
            $destroyToken       =   $accessTokenModel->destroySessions($userId);

            if ($userStatus == Constants::USER_STATUS_BANNED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status banned'
                ]);
            }
            if ($userStatus == Constants::USER_STATUS_INACTIVE) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status in-active'
                ]);
            }

            if ($userStatus == Constants::USER_STATUS_UNVERIFIED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status un-verified'
                ]);
            }
            $token              =   $user->createToken('findUr-app')->accessToken;
            $deviceUpdateStatus = false;
            DB::commit();
            if ($userStatus == Constants::USER_STATUS_PERMIT_PROFILE_INCOMPLETE) {
                $email   =   $user->email;
                $check = Mail::raw("Your Registration is on the Waiting List. You'll be able to use the application as soon as the Admin Approves your Registration", function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Account Registration FindUr App')->from(env('MAIL_FROM'));
                });
                return response()->json([
                    'status'        =>  200,
                    'success'       =>  true,
                    'message'       =>  'Account details created successfully',
                    'data'          =>  'Registration Pending, Waiting for Admin Approval'
                ]);
            }
            $success = array(
                'user_id'       => $userId,
                'username'      => $user->username,
                'email'         => $user->email,
                'user_role'     => $user->role_id,
                'status'        => $user->status,
                'token'         => "Bearer " . $token,
                'profile_image' => $user->profile_image,
                'thumb_image'   => $user->thumb_image,
                'device_token'  => $deviceUpdateStatus == true ? 'Updated device token' : 'Not updated device token'
            );
            return response()->json([
                'status'        =>  200,
                'success'       =>  true,
                'message'       =>  'Account details created successfully',
                'data'          =>  $success
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  500,
                'success'   =>  false,
                'error'     =>  [
                    'message'   => $e->getMessage ()
                ]
            ],500);
        }
    }

    /**
     * User : login
     */
    public function login(userlogin $request)
    {
        try {
            $input      =   $request->all();
            if (!Auth::attempt(['email' => $input['email'], 'password' => $input['password']])) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Try Again ! Email/Password is incorrect',
                ], 400);
            }
            $user               =   Auth::user();
            $accessTokenModel   =   new AccessToken();
            $userModel          =   new User();
            $userId             =   $user->id;
            $userStatus         =   $user->status;
            if ($userStatus == Constants::USER_STATUS_BANNED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status banned'
                ], 400);
            }
            if ($userStatus == Constants::USER_STATUS_INACTIVE) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     => 'User status in-active'
                ], 400);
            }
            if ($userStatus == Constants::USER_STATUS_UNVERIFIED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     => 'User status un-verified'
                ], 400);
            }

            if ($userStatus == Constants::USER_STATUS_DELETED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     => 'User status deleted'
                ], 400);
            }

            $deviceUpdateStatus = false;
            if (isset($input['device_token'])) {
                $deviceUpdateStatus = true;
                $updateData['device_token'] =  $input['device_token'];
                $updateData['updated_at'] =  $input['current_time'];
            }
            $updateData['last_login']   =   $input['current_time'];
            $updateData['platform']   =   $input['platform'];
            $updatedUser = $userModel->updateUser($user->id, $updateData);
            if (!$updatedUser) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     => 'Error updating device token'
                ], 400);
            }

            $destroyToken       =   $accessTokenModel->destroySessions($userId);
            $token              =   $user->createToken('findUr-app')->accessToken;

            DB::commit();

            $success = array(
                'user_id'       => $userId,
                'username'      => $user->username,
                'email'         => $user->email,
                'user_role'     => $user->role_id,
                'status'        =>  $userStatus,
                'platform'      =>  $updatedUser->platform,
                'token'         => "Bearer " . $token,
                'profile_image' => $user->profile_image,
                'thumb_image'   => $user->thumb_image,
                'device_token'  => $deviceUpdateStatus == true ? 'Updated device token' : 'Not updated device token'
            );
            return response()->json([
                'status'        =>  200,
                'success'       =>  true,
                'message'       =>  'logged in successfully',
                'data'          =>  $success
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
     * User: Request reset password
     */
    public function requestResetPassword(resetPassword $request)
    {
        try {
            DB::beginTransaction();
            $userModel =  new User();
            $input = $request->all();
            $email = $input['email'];
            $currentTime = $input['current_time'];
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'Please enter a valid email'
                ], 400);
            }


            $userId = $user->id;
            $digits = 4;
            $verificationCode       = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
            $verificationCodeExpiry = Carbon::parse($input['current_time'])->addDays(7);
            $verificationCodeExpiry = $verificationCodeExpiry->toArray();
            $resetPasswordData['verification_code'] =   $verificationCode;
            $resetPasswordData['verify_code_expiry']    =   $verificationCodeExpiry['formatted'];
            $resetPasswordData['updated_at']    =   $currentTime;
            $data = $userModel->updateUser($userId, $resetPasswordData);
            if (!$data) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     => 'Error generating verification code'
                ], 400);
            }
            $check = Mail::raw("FindUr App reset password code is: $verificationCode", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Account Verification Code - FindUr App')->from(env('MAIL_FROM'));
            });
            DB::commit();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   => 'Verification code generated successfully',
                'data'      =>  $data
            ], 200);
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
     * User: Account Verification
     */
    public function accountVerification(userVerification $request)
    {
        try {
            DB::beginTransaction();
            $email = $request->input('email');
            $verifyCode = $request->input('verify_code');
            $currentTime = $request->input('current_time');
            $filter     =   $request->input('filter');
            $userModel = new User();
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'Please enter a valid email'
                ], 400);
            }
            $userId = $user->id;

            if ($user->verify_code_expiry !=NULL  && $user->verify_code_expiry < $currentTime) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'Verification code expired',
                ], 400);
            }

            if ($user->verification_code != $verifyCode) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'Please enter a valid verification code'
                ], 400);
            }

            $status =   ($filter == 'signup') ? Constants::USER_STATUS_USER_DETAILS_INCOMPLETE_INT : $this->getCurrentStatus($user);


            if ($filter == 'forgot') {
                $userStatus         =   $user->status;
                if ($userStatus == Constants::USER_STATUS_BANNED) {
                    return response()->json([
                        'status'    =>  400,
                        'success'   =>  false,
                        'error'     =>  'User status banned'
                    ], 400);
                }
                if ($userStatus == Constants::USER_STATUS_INACTIVE) {
                    return response()->json([
                        'status'    =>  400,
                        'success'   =>  false,
                        'error'     => 'User status in-active'
                    ], 400);
                }
                if ($userStatus == Constants::USER_STATUS_UNVERIFIED) {
                    $status =  Constants::USER_STATUS_USER_DETAILS_INCOMPLETE_INT;
                }
            }

            $verifydata['status']               =   $status;
            $verifydata['verification_code']    =   NULL;
            $verifydata['verify_code_expiry']   =   NULL;
            $verifydata['updated_at']           =   $currentTime;

            $updatedAccount     =   $userModel->updateUser($userId, $verifydata);
            if (!$updatedAccount) {
                return response()->json(
                    [
                        'status'    =>  400,
                        'success'   =>  false,
                        'error'     =>  'Error verifying user account'
                    ],
                    400
                );
            }

            DB::commit();
            return response()->json(
                [
                    'status'    =>  200,
                    'success'   =>  true,
                    'message'   =>  'Account verified successfully',
                    'data'      =>  $updatedAccount
                ],
                200
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
     * Get user status : integer
     */
    public function getCurrentStatus($user)
    {
        $status =   $user->status;
        switch ($status) {
            case 'profile_incomplete':
                $intStatus = 4;
                break;

            case 'user_details_incomplete':
                $intStatus = 5;
                break;

            case 'admin_permmision_required':
                $intStatus = 6;
                break;

            case 'profile_incomplete_admin_permmision_required':
                $intStatus = 7;
                break;

            default:
                $intStatus = 1;
                break;
        }
        return $intStatus;
    }

    /**
     * User : Change password
     */
    public function changePassword(changePassword $request)
    {
        try {
            DB::beginTransaction();
            $userModel      =   new User();
            $input          =   $request->all();
            $email         =   $input['email'];
            $newPassword    =   $input['new_password'];
            $currentTime    =   $input['current_time'];

            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'error' =>  'Please enter a valid email'
                ], 400);
            }
            $userId = $user->id;

            $changedUserPassword['password']        = bcrypt($newPassword);
            $changedUserPassword['updated_at']      =   $currentTime;


            $updatedUser = $userModel->updateUser($userId, $changedUserPassword);
            if (!$updatedUser) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Error updating password'
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'     =>  'Password updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'   =>  500,
                'success'   =>  false,
                'error'   =>  [
                    'message'   =>  $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Admin : Login
     */
    public function adminLogin(userlogin $request)
    {
        try {
            $input      =   $request->all();
            if (!Auth::attempt(['email' => $input['email'], 'password' => $input['password']])) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Try Again ! Email/Password is incorrect',
                ],400);
            }
            $user               =   Auth::user();
            $accessTokenModel   =   new AccessToken();
            $userModel          =   new User();
            $userId             =   $user->id;
            $userStatus         =   $user->status;
            $userRole           =   $user->role_id;

            if ($userRole != 'admin') {
                return response()->json([
                    'status'    =>  403,
                    'success'   =>  false,
                    'error'     => 'Not authorized'
                ], 403);
            }

            if ($userStatus == Constants::USER_STATUS_BANNED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status banned'
                ], 400);
            }

            if ($userStatus == Constants::USER_STATUS_INACTIVE) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status in-active'
                ], 400);
            }

            if ($userStatus == Constants::USER_STATUS_UNVERIFIED) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'User status un-verified'
                ], 400);
            }

            $deviceUpdateStatus = false;
            if (isset($input['device_token'])) {
                $deviceUpdateStatus = true;
                $updateData['device_token'] =  $input['device_token'];
                $updateData['updated_at'] =  $input['current_time'];
            }
            $updateData['last_login']   =   $input['current_time'];
            $updatedUser = $userModel->updateUser($user->id, $updateData);
            if (!$updatedUser) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>   'Error updating device token'
                ], 400);
            }

            // $destroyToken       =   $accessTokenModel->destroySessions($userId);
            $token              =   $user->createToken('findUr-app')->accessToken;

            DB::commit();

            $success = array(
                'user_id'       => $userId,
                'username'      => $user->username,
                'email'         => $user->email,
                'user_role'     => $user->role_id,
                'token'         => "Bearer " . $token,
                'profile_image' => $user->profile_image,
                'thumb_image'   => '',
                'device_token'  => $deviceUpdateStatus == true ? 'Updated device token' : 'Not updated device token'
            );
            return response()->json([
                'status'        =>  200,
                'success'       =>  true,
                'message'       =>  'logged in successfully',
                'data'          =>  $success
            ], 200);
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

     /**\
     * User Logout
     */
    public function userLogout(Request $request)
    {
        try {
            $user   =   Auth::user();
            $accessTokenModel   =   new AccessToken();
            $userModel          =   new User();
            DB::beginTransaction();
            $token  =   $accessTokenModel->destroySessions($user->id);
            $updateUserData['device_token'] =   '';
            $updateUserData['updated_at']   =   $request->input('current_time');
            $updatedData    =   $userModel->updateUser($user->id, $updateUserData);
            if (!$updatedData) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Error loging out user'
                ], 400);
            }
            DB::commit();
            return response()->json([
                'status'        =>  200,
                'success'       =>  true,
                'message'       =>  'User logout successfully'
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
     * User change password
     */
    public function changeUserPassword(changeUserPassword $request)
    {
        try {
            $user   =   Auth::user();
            $userModel  =   new User();
            $userPassword   =   $user->password;
            $oldPassword    =   $request->input('old_password');
            if (!Hash::check($oldPassword, $userPassword)) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Please enter valid old password'
                ], 400);
            }
            $newpasswordData['password']    =   bcrypt($request->input('new_password'));
            $newpasswordData['updated_at']  =   $request->input('current_time');
            DB::beginTransaction();
            $updateUser =  $userModel->updateUser($user->id, $newpasswordData);
            if (!$updateUser) {
                return response()->json([
                    'status'    =>  400,
                    'success'   =>  false,
                    'error'     =>  'Error updating password'
                ], 400);
            }
            DB::commit();
            return response()->json([
                'status'    =>  200,
                'success'   =>  true,
                'message'   =>  'User password changed successfuly'
            ], 200);
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
}
