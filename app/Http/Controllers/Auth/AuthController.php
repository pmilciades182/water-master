<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'El email es requerido',
            'email.email' => 'El email debe tener un formato válido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta al administrador.'
                ]);
            }

            $user->updateLastLogin();
            
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->withInput();
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ], [
            'company_name.required' => 'El nombre de la empresa es requerido',
            'name.required' => 'El nombre es requerido',
            'email.required' => 'El email es requerido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'terms.accepted' => 'Debes aceptar los términos y condiciones',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Crear empresa
            $company = \App\Models\Company::create([
                'name' => $request->company_name,
                'subdomain' => $this->generateUniqueSubdomain($request->company_name),
                'email' => $request->email,
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
                'settings' => [
                    'currency' => 'PYG',
                    'timezone' => 'America/Asuncion',
                    'language' => 'es',
                ],
                'billing_config' => [
                    'tax_rate' => 10,
                    'invoice_prefix' => 'FAC-',
                    'next_invoice_number' => 1,
                ],
            ]);

            // Crear usuario
            $user = User::create([
                'company_id' => $company->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'provider' => 'local',
                'is_active' => true,
                'email_verified_at' => now(), // Auto-verificar por ahora
            ]);

            Auth::login($user);

            return redirect('/dashboard')->with('success', 'Registro exitoso. ¡Bienvenido a Water Master!');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al crear la cuenta: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login')->with('success', 'Sesión cerrada correctamente');
    }

    private function generateUniqueSubdomain(string $companyName): string
    {
        $subdomain = \Illuminate\Support\Str::slug($companyName);
        $originalSubdomain = $subdomain;
        $counter = 1;

        while (\App\Models\Company::where('subdomain', $subdomain)->exists()) {
            $subdomain = $originalSubdomain . '-' . $counter;
            $counter++;
        }

        return $subdomain;
    }
}