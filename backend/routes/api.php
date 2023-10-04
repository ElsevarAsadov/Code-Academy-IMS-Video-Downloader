<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use \App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\CheckAccessToken;
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
        $token = $tokenResponse->json()['access_token'];
        //if verification code is valid
        if (isset($token)) {

            $userDataReponse = Http::withToken($token)->get('https://api.github.com/user');
            //check if user is already in db
            $existing_token = User::where('id', (int)$userDataReponse['id'])->first();
            if(!$existing_token){
                //creating user data in DB
                $newUser = new User();
                $newUser->id = (int)$userDataReponse['id'];
                $newUser->name = $userDataReponse['name'];
                $newUser->email = $userDataReponse['email'];
                $newUser->avatar = $userDataReponse['avatar_url'];
                $newUser->token = $token;
                $newUser->github_repo = sprintf('https://github.com/%s', $userDataReponse['login']);

                $newUser->save();
            }else{
                return response()->json(['access_token'=>$existing_token->token]);
            }

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

//saves cookies in db
Route::patch('/save-cookies/{id}', function(Request $request, $id) {
    try {
        $request->validate([
            'PHPSESSID' => 'required',
            'login_token'=> 'required',
            'cookie_microstats_visibility'=> 'required'
        ]);
        if(count($request->all()) !== 3){
            throw new Exception("too many payload args");
        }
    }catch (Exception){
        return response()->json(['err'=>'Invalid request payload', ], 400);
    }

    $user = User::where('id', $id)->first();
    if($user){
        $user->update(['cookies'=>$request->all()]);
        return response()->json(['msg'=>'User Cookies Patched Successfully'], 200);
    }else{
        return response()->json(['err'=>'Something happened when patching user cookies'], 409);
    }
})->middleware([CheckAccessToken::class]);
