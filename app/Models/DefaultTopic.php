<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultTopic extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $table = 'default_topics';

    public function getStatusAttribute($value)
    {
        switch ($value)
        {
            case '1':
                $status =   'Active';
                break;
            case '2':
                $status =   'In-Active';
                break;
            default:
            $status = '';
            break;
        }
        return $status;
    }
    
}
