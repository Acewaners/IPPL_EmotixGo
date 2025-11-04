<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}
