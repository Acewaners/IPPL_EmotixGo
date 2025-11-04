<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(){
        return User::select('user_id','name','email','role','is_admin')->paginate(20);
    }

    public function updateRole(Request $r, User $user){
        $r->validate(['role'=>'required|in:buyer,seller']);
        if($user->is_admin) abort(422,'Tidak boleh mengubah role admin.');
        $user->update(['role'=>$r->role]);
        return $user->only('user_id','name','email','role');
    }
}
