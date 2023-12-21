<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSearches extends Model
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'searchable_id',
        'created_at',
        'updated_at'
    ];

    protected $table = 'user_recent_searches';


    public function searchUser()
    {
        return $this->belongsToMany(User::class,'user_recent_searches','user_id', 'searchable_id');
    }

    public function searchableUser()
    {
        return $this->belongsToMany(User::class, 'user_recent_searches', 'searchable_id','user_id');
    }
}
