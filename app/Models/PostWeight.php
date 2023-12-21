<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PostWeight extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected 	$table 			= 'post_weights';
    protected 	$primaryKey 	= 'id';
    public 		$timestamps 	= false;
    protected 	$fillable 		=
    [
    'rule',
    'weight',
    'status',
    'created_at',
    'updated_at',
    ];

    /*
    * This method user for update admin configs
    */
    public function updateWeight($condition, $dataToUpdate){
        return $weightUpdate = PostWeight::where('rule', $condition)->update($dataToUpdate);
    }
    /*
    * This method user for get admin configs
    */
    public function getWeight()
    {
        return $get = PostWeight::select('*')->get();
    }

     /**
     * This method is used to get specifc value of rule
     */
    public function getPostWeightageRule($rule)
    {
        return $get = PostWeight::where('rule',$rule)->first();
    }

}
