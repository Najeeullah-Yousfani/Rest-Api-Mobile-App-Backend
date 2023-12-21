<?php

namespace App\Models;

use App\Models\CustomAddsGender as ModelsCustomAddsGender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomAddsGender extends Model
{
    use HasFactory;
    protected $fillable = [
        'custom_ads_id',
        'gender',
        'created_at',
        'updated_at'
    ];

    protected $table = 'custom_ads_gender';

    public function createCustomAddsGenders($customAdds,$genders)
    {
        $data =  [];
        foreach($genders as $gender)
        {
            array_push($data,['custom_ads_id'=>$customAdds->id,'gender'=>$gender]);
        }
        return  $records    =   CustomAddsGender::insert($data);
    }

    public function updateCustomAddsGender($customAdds, $genders)
    {
        $customAddsId   =   $customAdds->id;
        $data           =   CustomAddsGender::where('custom_ads_id',$customAddsId)->delete();
        return $data    =   $this->createCustomAddsGenders($customAdds,$genders);
    }
}
