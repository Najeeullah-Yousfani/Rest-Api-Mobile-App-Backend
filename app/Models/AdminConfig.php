<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AdminConfig extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected 	$table 			= 'admin_configs';
    protected 	$primaryKey 	= 'id';
    public 		$timestamps 	= false;
    protected 	$fillable 		=
    [
    'rule',
    'value',
    'status',
    'created_at',
    'updated_at',
    ];
     /*
    * This method use for get post status.
    */
    // public function getStatusAttribute($value)
    // {
    //     $status = '';
    //     switch ($value) {
    //         case 1:
    //             $status =  'true';
    //             break;
    //         case 2:
    //             $status = 'false';
    //             break;
    //         default:
    //             $status = '';
    //             break;
    //     }
    //     return $status;
    // }
    /*
    * This method user for update admin configs
    */
    public function updateAdminConfig($condition, $dataToUpdate){
        return $adminConfigUpdate = AdminConfig::where('rule', $condition)->update($dataToUpdate);
    }
    /*
    * This method user for get admin configs
    */
    public function getConfig()
    {
        return $get = AdminConfig::select('*')->get();
    }

    /**
     * This method is used to get specifc value of rule
     */
    public function getSpecificConfig($rule)
    {
        return $get = AdminConfig::where('rule',$rule)->first();
    }

}
