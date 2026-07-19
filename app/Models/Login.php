<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Hidden(['Password', 'OTP_CODE', 'profile_picture', 'profile_picture_mime'])]
class Login extends Authenticatable
{
    protected $table = 'logins';

    protected $primaryKey = 'login_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'account_type',
        'User_ID',
        'Email',
        'Password',
        'access_modules',
        'User_First_Name',
        'User_Middle_Name',
        'User_Last_Name',
        'Gender',
    ];

    public function getAuthPasswordName(): string
    {
        return 'Password';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->Password;
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim(collect([
            $this->User_First_Name,
            $this->User_Middle_Name,
            $this->User_Last_Name,
        ])->filter()->implode(' '));

        return $name !== '' ? $name : (string) $this->User_ID;
    }
}
