<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class AccessToken extends Model
{
    use HasApiTokens, Notifiable;
    protected 	$table 			= 'oauth_access_tokens';
    protected 	$primaryKey 	= 'id';
    public 		$timestamps 	= false;
    protected 	$fillable 		=
    [
    'user_id',
    'client_id',
    'name',
    'scopes',
    'revoked',
    'created_at',
    'updated_at',
    'expires_at',
    ];


    public function destroySessions($userId){
        return $destroy = AccessToken::where('user_id', $userId)->delete();
    }
}
