<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{


     public function showForm()
    {
        return view('user_form');
    }


public function store(Request $request)
{
    $request->validate([
        'username' => 'required|string|max:100|unique:users,username',
    ]);

 
     $user = \App\Models\User::create([
        'username' => $request->username,
    ]);

     session([
        'user_id' => $user->id,
        'asked_questions' => [],
        'quiz_stats' => ['correct' => 0, 'wrong' => 0, 'skipped' => 0],
    ]);

    // ✅ Delete any old leftover results (same username)
    \App\Models\UserResult::where('user_id', $user->id)->delete();

    return response()->json(['user_id' => $user->id]);
}

  


 
}
