<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->withErrors(['error' => 'Proveedor no válido']);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, Request $request)
    {
        try {
            if (!in_array($provider, ['google', 'facebook'])) {
                return redirect()->route('login')->withErrors(['error' => 'Proveedor no válido']);
            }

            $socialUser = Socialite::driver($provider)->user();
            
            // Buscar si ya existe el usuario por provider_id
            $user = User::where('provider', $provider)
                       ->where('provider_id', $socialUser->id)
                       ->first();

            if (!$user) {
                // Buscar por email en caso de que ya exista con otro provider
                $existingUser = User::where('email', $socialUser->email)->first();
                
                if ($existingUser) {
                    // Usuario ya existe con email, vincular provider
                    $existingUser->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->id,
                        'avatar' => $socialUser->avatar,
                    ]);
                    $user = $existingUser;
                } else {
                    // Crear nuevo usuario y empresa demo
                    $company = $this->createDemoCompany($socialUser->email);
                    
                    $user = User::create([
                        'company_id' => $company->id,
                        'name' => $socialUser->name,
                        'email' => $socialUser->email,
                        'provider' => $provider,
                        'provider_id' => $socialUser->id,
                        'avatar' => $socialUser->avatar,
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }

            if (!$user->is_active) {
                return redirect()->route('login')->withErrors([
                    'error' => 'Tu cuenta está desactivada. Contacta al administrador.'
                ]);
            }

            // Actualizar última conexión
            $user->updateLastLogin();

            Auth::login($user, true);

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'error' => 'Error en la autenticación: ' . $e->getMessage()
            ]);
        }
    }

    private function createDemoCompany(string $email): Company
    {
        $subdomain = Str::slug(explode('@', $email)[0]) . '-' . Str::random(4);
        
        // Asegurar que el subdomain sea único
        while (Company::where('subdomain', $subdomain)->exists()) {
            $subdomain = Str::slug(explode('@', $email)[0]) . '-' . Str::random(4);
        }

        return Company::create([
            'name' => 'Aguetería Demo',
            'subdomain' => $subdomain,
            'email' => $email,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30), // 30 días de prueba
            'settings' => [
                'currency' => 'PYG',
                'timezone' => 'America/Asuncion',
                'language' => 'es',
            ],
            'billing_config' => [
                'tax_rate' => 10, // IVA 10% Paraguay
                'invoice_prefix' => 'FAC-',
                'next_invoice_number' => 1,
            ],
        ]);
    }
}
