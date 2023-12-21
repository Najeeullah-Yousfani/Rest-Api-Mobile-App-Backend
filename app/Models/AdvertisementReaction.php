<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class AdvertisementReaction extends Model
{
    use HasApiTokens, Notifiable;
    protected     $table          = 'advert_reactions';
    protected     $primaryKey     = 'id';
    public        $timestamps     = false;
    protected     $fillable       =
    [
        'id',
        'user_id',
        'advert_like_id',
        'advert_dislike_id',
        'status',
        'created_at',
    ];
    /*
    * This method is used for add post reaction.
    */
    public function addAdvertReaction($data)
    {
        return $add = AdvertisementReaction::create($data);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adLiked()
    {
        return $this->belongsTo(CustomAdds::class, 'advert_like_id');
    }

    public function adDisliked()
    {
        return $this->belongsTo(CustomAdds::class, 'advert_dislike_id');
    }

}
