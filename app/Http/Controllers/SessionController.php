<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class SessionController extends Controller
{
   
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $attribute = request()->validate([
            'email' => ['required', 'email'],
            'password' => ['requied']
        ]);
        if (! Auth::attempt($attribute)) {
            throw ValidationException::withMessages([
                'email' => 'sorry, those credentials do not match.'
            ]);
        }
        request()->session()->regenerate();
        return redirect('/');
    }

    
    public function destroy(string $id)
    {
        Auth::logout();
        return redirect('/');
    }
}
