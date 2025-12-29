<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $req){
        $data = $req->validate([
            'name'=>['required','string','max:100'],
            'email'=>['required','email','max:100','unique:users,email'],
            'password'=>['required','string','min:6'],
        ]);
        $user = User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
            'role'=>'buyer',
        ]);
        return response()->json(['message'=>'Registered','user'=>$user],201);
    }

    public function login(Request $req){
        $cred = $req->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email',$cred['email'])->first();
        if(!$user || !Hash::check($cred['password'],$user->password)){
            return response()->json(['message'=>'Unauthorized access'],401);
        }
        $token = $user->createToken('spa')->plainTextToken;
        return response()->json(['token'=>$token,'user'=>$user]);
    }

    public function me(Request $req){
        return $req->user();
    }

    public function logout(Request $req){
        $req->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Logged out']);
    }

    public function updateProfile(Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);
        $user->update($validated);
        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }

    public function updatePassword(Request $request) {
        // Pastikan user diambil dari Auth::user() atau $request->user()
        $user = auth('sanctum')->user(); 

        if (!$user) {
            Log::error('Update Password Failed: User not found in session.');
            return response()->json(['message' => 'User not found.'], 401);
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Lakukan pengecekan manual agar debug lebih jelas
        if (!Hash::check($request->current_password, $user->password)) {
            Log::error('Password Mismatch for User:', ['id' => $user->id, 'email' => $user->email]);
            return response()->json([
                'message' => 'The password is incorrect.',
                'errors' => ['current_password' => ['Password lama yang Anda masukkan salah.']]
            ], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        
        Log::info('Password Update Success for User ID: ' . $user->id);
        return response()->json(['message' => 'Password changed successfully']);
    }
}
