<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function profile()
    {
        return view('admin.profile', [
            'admin' => Auth::user(),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function settings()
    {
        return view('admin.settings', [
            'admin' => Auth::user(),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function avatar(): Response
    {
        $admin = Auth::user();

        abort_unless($admin && $admin->profile_picture, 404);

        return response($admin->profile_picture, 200, [
            'Content-Type' => $admin->profile_picture_mime ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
