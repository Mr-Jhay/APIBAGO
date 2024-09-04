<?php

namespace App\Http\Controllers;
use App\Models\Announcement;
use App\Notifications\AnnouncementCreated;
use App\Models\User;

use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    //
    public function store99(Request $request){
        $announcement=Announcement::create([
            'title'=>$request->title,
            'description'=>$request->description
        ]);
//sending to user
$user = User::first();
if ($user) {
    $user->notify(new AnnouncementCreated($announcement));
} else {
    return response()->json(['error' => 'No user found'], 404);
}


//sending to email
//sending to multiple user

        return response()->json($announcement);
    }
}
