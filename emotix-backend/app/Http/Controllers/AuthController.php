<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

    public function updateProfile(Request $req)
    {
        $user = $req->user();

        // --- KODE DEBUG UNTUK LARAVEL.LOG ---
        Log::info('Debug Update Profile:', [
            'user_obj_id_attribute' => $user->id,        // Cek apakah 'id' ada
            'user_obj_real_primary' => $user->user_id,   // Cek primary key asli Anda
            'input_email'           => $req->email,
            'current_db_email'      => $user->email
        ]);

        $data = $req->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 
                'email', 
                'max:100', 
                // PERBAIKAN: Gunakan $user->user_id karena itu primary key Anda
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'), 
            ],
        ]);

        try {
            $user->update($data);
            Log::info('Update Profile Success for ID: ' . $user->user_id);
            
            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Update Profile Error: ' . $e->getMessage());
            return response()->json(['message' => 'Update failed'], 500);
        }
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
