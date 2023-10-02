<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use \App\Models\User;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::get("/get-accessToken", function (Request $request) {
    $code = $request->input('code');
    $tokenURL = sprintf("https://github.com/login/oauth/access_token?client_id=%s&client_secret=%s&code=%s",
        env("GITHUB_CLIENT_ID"), env("GITHUB_CLIENT_SECRET"), $code
    );
    $tokenResponse = Http::withHeaders(['Accept' => 'application/json'])->post($tokenURL);

    if ($tokenResponse->successful()) {
        if ($tokenResponse->status() == 200) {
            return $tokenResponse->json();
        }
    } else {
        return response()->json(['Some Error Happened When Requesting github.com'], 500);
    }
});

Route::get('/get-userData', function (Request $request) {
    //get the pure token format.
    $token = trim(str_replace("Bearer", "", $request->header('Authorization')));
    $userDataReponse = Http::withToken($token)->get('https://api.github.com/user');
    if ($userDataReponse->successful()) {
        if ($userDataReponse->status() == 200) {
            return $userDataReponse->json();
        }
    }else {
        return response()->json(['error'=>'Some Error Happened When Requesting github.com'], 500);
    }
});

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});


//Route::get('/auth/callback', function () {
//    $user = Socialite::driver('github')->stateless()->user();
//    $token = $user->token;
//
//    if(!User::where('token', $user->token)->exists()){
//        $new_user = new User();
//        $new_user->id = $user->id;
//        $new_user->name = $user->user['login'];
//        $new_user->email = $user->user['email'];
//        $new_user->avatar = $user->avatar;
//        $new_user->token = $user->token;
//        $new_user->github_repo = 'https://www.github.com/'.$user->user['login'];
//        $new_user->active = false;
//
//        $new_user->save();
//    }
//
//    return redirect('http://localhost:5173/Code-Academy-IMS-Video-Downloader/?token=' . $token);
//    });
//
//Route::get('/get-user-data', function(Request $request){
//    $token = $request->header('Authorization');
//    $token = trim(str_replace("Bearer", "", $token));
//    if(!$token){
//        return response()->json(['error' => 'Unauthorized'], 401);
//    };
//
//    $user = User::where('token', $token)->first();
//
//    return response()->json($user);
//
//});
