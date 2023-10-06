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
        $cookies = json_decode($user->cookies, true);
        $cookies = 'cookie_microstats_visibility=' . $cookies['cookie_microstats_visibility'] . ';' . 'login_token=' . $cookies['login_token'] . ';' . ' PHPSESSID=' . $cookies['PHPSESSID'];
        $video_name = Uuid::uuid4()->toString() . '.mp4';
        exec(sprintf('python "%s" "%s" "https://lms.code.edu.az/unit/view/id:%s" "%s" "False"', base_path('/business_logic/video_downloader/main.py'), $cookies, $video_id, $video_name), $result, $exit_code);
        $exit_code = 100;
        if($exit_code === 100){
            $files = new \App\Models\DeleteFiles();
            $files->files = storage_path('app/video_cache/'.$video_name);
            $files->save();
            return response()->download(storage_path('app/video_cache/'.$video_name), 'video.mp4', [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'attachment; filename="video.mp4"',
            ]);
        }
        else{
            return response()->status(400);
        }

});


//Route::get('/download/{id}', function(Request $request, $id){
//    return response()->stream(function () use ($id, $request) {
//        $user = User::where('id', $id)->first();
//        $cookies = json_decode($user->cookies, true);
//        $cookies = 'cookie_microstats_visibility=' . $cookies['cookie_microstats_visibility'] . ';' . 'login_token=' . $cookies['login_token'] . ';' . ' PHPSESSID=' . $cookies['PHPSESSID'];
//        exec(sprintf('python "C:\Users\Es\Desktop\CA-Video\backend\business_logic\video_downloader\main.py" "%s" "https://lms.code.edu.az/unit/view/id:7386" "xaxa.mp4" "True"', $cookies), $filesize, $ex);
//        $filesize = $filesize[0];
//        exec(sprintf('python "C:\Users\Es\Desktop\CA-Video\backend\business_logic\video_downloader\main.py" "%s" "https://lms.code.edu.az/unit/view/id:7386" "xaxa.mp4" "False"', $cookies), $filesize, $ex);
//        while (true) {
//            echo (string) filesize('C:\Users\Es\Desktop\CA-Video\backend\storage\app\video_cache\xaxa.mp4');
//            ob_flush();
//            flush();
//
//            // Break the loop if the client aborted the connection (closed the page)
//            if (connection_aborted()) {break;}
//            usleep(50000); // 50ms
//        }
//    }, 200, [
//        'Cache-Control' => 'no-cache',
//        'Content-Type' => 'application/json',
//    ]);
//});
