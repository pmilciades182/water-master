<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function user(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['user' => null], 401);
        }

        // Load the company relationship
        $user->load('company');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'provider' => $user->provider,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
                'company' => [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'subdomain' => $user->company->subdomain,
                    'email' => $user->company->email,
                    'phone' => $user->company->phone,
                    'address' => $user->company->address,
                    'tax_id' => $user->company->tax_id,
                    'logo_url' => $user->company->logo_url,
                    'is_active' => $user->company->is_active,
                    'trial_ends_at' => $user->company->trial_ends_at,
                    'settings' => $user->company->settings,
                    'billing_config' => $user->company->billing_config,
                ]
            ]
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // TODO: Implement actual stats from database
        // For now, return mock data
        $stats = [
            'clients' => 0,
            'services' => 0,
            'invoices' => 0,
            'products' => 0,
        ];

        return response()->json([
            'stats' => $stats,
            'company' => $user->company,
        ]);
    }
}
