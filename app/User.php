<?php

namespace App;

use App\Models\Donation;
use App\Models\KoopOrder;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\PickUpOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = ['name', 'email', 'password', 'telno', 'remember_token'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id');
    }

    public function organizationRole()
    {
        return $this->belongsToMany(OrganizationRole::class, 'organization_user', '', 'role_id');
    }
    
    public function donation()
    {
        return $this->belongsToMany(Donation::class, 'donation_user');
    }

    public function pickup_order()
    {
        return $this->hasOne(PickUpOrder::class);
    }

    public function getUserById()
    {
        $id = Auth::id();
        // dd($id);
        $user = auth()->user();
        
        return $user;
    }

    public function getUser($id)
    {
        $user = User::find($id);
        return $user;
    }

}
