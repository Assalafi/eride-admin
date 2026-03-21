<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            Log::info('API: Login attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('API: Login failed - invalid credentials', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                ], 401);
            }

            $token = $user->createToken('mobile-app')->plainTextToken;

            Log::info('API: Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->roles->first()->name ?? 'User',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()->name ?? 'User',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: Login error', [
                'email' => $request->email ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: Logout initiated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            $request->user()->currentAccessToken()->delete();

            Log::info('API: Logout successful', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('API: Logout error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout',
            ], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: User info accessed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()->name ?? 'User',
                    'branch' => $user->branch,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: User info error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user info',
            ], 500);
        }
    }
}
