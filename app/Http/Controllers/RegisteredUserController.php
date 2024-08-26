<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
   

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userAttributes = $request->validate([
            'name' =>['required'],
            'email' => ['required', 'email', 'uunique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);
    
        $employerAttributes = $request->validate([
            'employer' =>['required'],
            'logo' => ['required', File::type(['png', 'jpg', 'webp'])],
            
        ]);
        $user = User::create($userAttributes);
        $logoPath = $request->logo->store('logos');
        $user->employer()->create([
            'name' => $employerAttributes['employer'],
            'logo' => $logoPath,
        ]);
        Auth::login($user);

        return redirect('/');
    }

   