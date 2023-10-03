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

//gives user access token to access website.
Route::get("/get-accessToken", function (Request $request) {
    $code = $request->input('code');
    $tokenURL = sprintf("https://github.com/login/oauth/access_token?client_id=%s&client_secret=%s&code=%s",
        env("GITHUB_CLIENT_ID"), env("GITHUB_CLIENT_SECRET"), $code
    );
    $tokenResponse = Http::withHeaders(['Accept' => 'application/json'])->post($tokenURL);

    if ($tokenResponse->successful()) {
        //if verification code is valid
        if (isset($tokenResponse->json()['access_token'])) {
            return $tokenResponse->json();
        }
        return response()->json(['err' => 'Wrong Verification Code'], 401);
    } else {
        return response()->json(['err' => 'Some Error Happened When Requesting github.com'], 500);
    }
});

//gets user github account data via access token.
Route::get('/get-userData', function (Request $request) {
    //get the pure token format : (Bearer xxx-yyy-zzz -> xxx-yyy-zzz) .
    $token = trim(str_replace("Bearer", "", $request->header('Authorization')));
    try {
        $userDataReponse = Http::withToken($token)->get('https://api.github.com/user');
        //if token is valid
        if ($userDataReponse->successful()) {
            return $userDataReponse->json();
        }
        //if user provided token is invalid
        else if ($userDataReponse->unauthorized()) {
            return response()->json(['err' => 'invalid token'], 401);
        }
        //if other kinda err happens
        return response()->json(['error' => 'Some Error Happened When Requesting github.com'], 500);

    } catch (Exception) {
        return response()->json(['error' => 'Some Error Happened When Requesting github.com'], 500);
    }
});
