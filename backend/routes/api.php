<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\CheckAccessToken;
use Ramsey\Uuid\Uuid;
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

ini_set('memory_limit', '3000M');
ini_set('max_execution_time', '0');

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
            $existing_user = User::where('id', (int)$userDataReponse['id'])->first();
            if (!$existing_user) {
                //creating user data in DB
                $newUser = new User();
                $newUser->id = (int)$userDataReponse['id'];
                $newUser->name = $userDataReponse['name'];
                $newUser->email = $userDataReponse['email'];
                $newUser->avatar = $userDataReponse['avatar_url'];
                $newUser->token = $token;
                $newUser->github_repo = sprintf('https://github.com/%s', $userDataReponse['login']);
                $newUser->save();
                return response()->json(['access_token' => $token]);

            } else {
                $existing_user->update(['token' => $token]);
                return response()->json(['access_token' => $existing_user->token]);
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
            $cookies = User::select('cookies')
                ->where('id', $userDataReponse['id'])
                ->first();

            $cookies = json_decode($cookies['cookies'], true);

            if ($cookies) {
                return response()->json(array_merge($userDataReponse->json(), $cookies), 200);
            } else {
                return response($userDataReponse->json(), 200);
            }
        } //if user provided token is invalid
        else if ($userDataReponse->unauthorized()) {
            return response()->json(['err' => 'invalid token'], 401);
        }
        //if other kinda err happens
        return response()->json(['error' => 'Some Error Happened When Requesting github.com'], 500);

    } catch (Exception $e) {
        return response()->json(['error' => 'Some Error Happened When Requesting github.com'], 500);
    }
});

//saves cookies in db
Route::patch('/save-cookies/{id}', function (Request $request, $id) {
    try {
        $request->validate([
            'PHPSESSID' => 'required',
            'login_token' => 'required',
            'cookie_microstats_visibility' => 'required'
        ]);
        if (count($request->all()) !== 3) {
            throw new Exception("too many payload args");
        }
    } catch (Exception) {
        return response()->json(['err' => 'Invalid request payload',], 400);
    }

    $user = User::where('id', $id)->first();

    if ($user) {
        $user->update(['cookies' => $request->all()]);
        return response()->json(['msg' => 'User Cookies Patched Successfully'], 200);
    } else {
        return response()->json(['err' => 'Something happened when patching user cookies'], 409);
    }
})->middleware([CheckAccessToken::class]);

Route::get("/download/{id}/{video_id}", function (Request $request, $id, $video_id) {
        $user = User::where('id', $id)->first();
        $userName = $user->github_repo;
        $k = explode('/', $userName);
        $url = sprintf('https://api.github.com/users/%s/starred', end($k));
        $starredRepos = Http::get($url)->json();
        $zzz = false;

        if(now()->diffInHours($user->updated_at) > 1){
            return response("Cookie datalarinizin vaxti kecibdir yenileyin", 400);
        };

        foreach($starredRepos as $repo){
            if(isset($repo['id'])){
                if($repo['id'] == 698004786){
                    $zzz = true;
                    break;
                };

            }
        }
        if(!$zzz){
            return response("Github Reposuna Star Vermeyibsen :(");
        }
        $cookies = json_decode($user->cookies, true);
        $cookies = 'cookie_microstats_visibility=' . $cookies['cookie_microstats_visibility'] . ';' . 'login_token=' . $cookies['login_token'] . ';' . ' PHPSESSID=' . $cookies['PHPSESSID'];
        $video_name = Uuid::uuid4()->toString() . '.mp4';
        exec(sprintf('python "%s" "%s" "https://lms.code.edu.az/unit/view/id:%s" "%s" "False"', base_path('/business_logic/video_downloader/main.py'), $cookies, $video_id, $video_name), $result, $exit_code);
        //return sprintf('python3 "%s" "%s" "https://lms.code.edu.az/unit/view/id:%s" "%s" "False"', base_path('/business_logic/video_downloader/main.py'), $cookies, $video_id, $video_name);
        //return ["stdout" => $result, "exit code" => $exit_code];
	if($exit_code === 100){
            $files = new \App\Models\DeleteFiles();
            $files->files = storage_path('app/video_cache/'.$video_name);
            $files->save();
            return response()->download(storage_path('app/video_cache/'.$video_name), 'video.mp4', [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'attachment; filename="video.mp4"',
            ])->deleteFileAfterSend(true);
        }
        else{
            return response('Xeta bas verdi xahis edirik cookie ve ya video id ni duzgun daxil edin :)', 400);
        }

});
