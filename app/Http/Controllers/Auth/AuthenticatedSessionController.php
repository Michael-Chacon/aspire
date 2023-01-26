<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;

use App\Providers\RouteServiceProvider;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json'
        ])->post('http://api.blog.test/v1/login', [
            'email' => $request->email,
            'password' => $request->password
        ]);

        if($response->status() == 404){
            return back()->withErrors('These credentials do not match our records.');
        }
        
        $service =  $response->json();
        $user = User::updateOrcreate([
            'email' => $request->email
        ],$service['data']);
        // Validar si el usuario que inicia sesión ya tiene un token asignado
        if(!$user->accessToken){
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
        }
        Auth::login($user, $request->remember);
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
