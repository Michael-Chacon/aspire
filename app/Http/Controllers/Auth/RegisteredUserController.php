<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:6', 'string'],
        ]);
         
        $response = Http::withHeaders([
            'Accept' => 'application/json'
         ])->post('http://api.blog.test/v1/register', [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, 
            'password_confirmation' => $request->password_confirmation
         ]);

         if($response->status() === 422){
            return back()->withErrors($response->json()['errors']);
         }
        
         $service = $response->json();
         
         $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
         ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json'
        ])->post('http://api.blog.test/oauth/token', [
            'grant_type' => 'password',
            'client_id' => '984f8d3e-125c-4d39-8623-b949d132da94', 
            'client_secret' => 'NQHCfV59dWhCcnbkOn1IRVJypxlRphJmeONPG8Vb',
            'username' => $request->email,
            'password' => $request->password
        ]);

        $access_token = $response->json();

        $user->accessToken()->create([
            'service_id' => $service['data']['id'],
            'access_token' => $access_token['access_token'],
            'refresh_token' => $access_token['refresh_token'],
            'expires_at' => now()->addSecond($access_token['expires_in'])
        ]);


        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
