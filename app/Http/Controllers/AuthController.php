<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Register New User
     */
    public function register(Request $request)
    {
        try {
            // Custom English Messages (Friendly UX)
            $messages = [
                'name.required' => 'Please enter your full name.',
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email is already registered. Please try logging in.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',
            ];

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'phone_number' => ['nullable', 'string', 'max:20'],
                'date_of_birth' => ['nullable', 'date'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], $messages);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'password' => Hash::make($request->password),
                'role' => 'customer',
                'is_active' => true,
            ]);

            $token = $user->createToken('customer-token')->plainTextToken;

            // Success Message
            return $this->successResponse([
                'user' => $user,
                'token' => $token,
            ], 'Account created successfully! Welcome aboard.', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed. Please check your inputs.', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('An unexpected error occurred during registration.', 500, $e->getMessage());
        }
    }

    /**
     * Login User
     */
    public function login(Request $request)
    {
        try {
            $messages = [
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'Please enter your password.',
            ];

            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ], $messages);

            $user = User::where('email', $request->email)->first();

            // Check password
            if (! $user || ! Hash::check($request->password, $user->password)) {
                // Clear and polite error message for user
                return $this->errorResponse('Invalid email or password.', 401);
            }

            // Check if active
            if (! $user->is_active) {
                return $this->errorResponse('Your account is currently inactive. Please contact support.', 403);
            }

            // Optional: Delete old tokens for security
            $user->tokens()->delete();

            $token = $user->createToken('api-token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
            ], 'Logged in successfully.');

        } catch (ValidationException $e) {
            return $this->errorResponse('Invalid input data.', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while logging in.', 500, $e->getMessage());
        }
    }

    /**
     * Logout User
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Logged out successfully.');
    }
}