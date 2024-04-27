<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ActiveCitizen extends Authenticatable 
{
    use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;
    protected $guarded = [];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * | Get Active Citizens by Moble No
     */
    public function getCitizenByMobile($mobile)
    {
        return ActiveCitizen::where('mobile', $mobile)
            ->first();
    }

    /**
     * | Citizen Registration
     */
    
}
