<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable =  [
        "reporter_id",
        "reportable_id",
        "reportable_type",
        "type",
        "body",
        "status",
        "created_at",
        "updated_at"
    ];

    protected $table = 'reports';


    public function reportable()
    {
        return $this->morphTo();
    }

}
