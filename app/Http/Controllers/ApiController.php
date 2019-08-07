<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterAuthRequest;
use App\User;
use Illuminate\Http\Request;
use JWTAuth;
use Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    public $loginAfterSignUp = true;
    public function redirectToFacebookProvider()
    {
        return Socialite::driver('facebook')->redirect();
    }
    public function handleProviderFacebookCallback()
    {

        $facebook_user = Socialite::driver('facebook')->user();
        $existUser = User::where('email',$facebook_user->email)->first();
        $auth_user = new User();
        $auth_user->name = $facebook_user->name;
        $auth_user->email = $facebook_user->email;
        // dd(gettype($existUser));
        if($existUser != null){
          $input = $auth_user->email;
          $jwt_token = null;

          if (!$jwt_token = JWTAuth::fromUser($existUser)) {
              return response()->json([
                  'success' => false,
                  'message' => 'Invalid Email or Password',
              ], 401);
          }

          return response()->json([
              'success' => true,
              'token' => $jwt_token,
          ]);
        }else{
            $token=$this->authToken($auth_user);
        }

        return $token;

    }
    public function register(RegisterAuthRequest $request)
    {
        $existUser = User::where('email',$request->email)->first();
        Log::info($existUser);
        if(empty($existUser))
        {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            try{
                $user->phone=$request->phone;
            }
            catch (Exception $e) {

            }
            $user->save();
            if ($this->loginAfterSignUp) {
                return $this->login($request);
            }

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);

        }
        else
        {
            Log::info("same");
            return response()->json([
                'success' => false,
                'message'=> "you registered with this mail before"
            ], 400);
        }

    }

    public function login(Request $request)
    {
        Log::info("login");
        $input = $request->only('email', 'password');
        $jwt_token = null;

        if (!$jwt_token = JWTAuth::attempt($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
            ], 401);
        }

        $existUser = User::where('email',$request->email)->first();

        return response()->json([
            'success' => true,
            'token' => $jwt_token,
            'data'=> $existUser,
        ]);
    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the user cannot be logged out'
            ], 500);
        }
    }

    public function getAuthUser(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }
    public function validateToken(Request $request)
    {
        try {
            $this->validate($request, [
                'token' => 'required'
            ]);
        } catch (JWTException $exception) {
            return response()->json(['success' => false]);
        }


        return response()->json(['success' => true]);
    }


    public function redirectToGoogleProvider()
  {
      return Socialite::driver('google')->redirect();
  }


  public function handleProviderGoogleCallback()
  {

          $google_user = Socialite::driver('google')->user();
          $existUser = User::where('email',$google_user->email)->first();
          $auth_user = new User();
          $auth_user->name = $google_user->name;
          $auth_user->email = $google_user->email;
          if($existUser != null){
            $input = $auth_user->email;
            $jwt_token = null;

            if (!$jwt_token = JWTAuth::fromUser($existUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Email or Password',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'token' => $jwt_token,
            ]);
          }else{
              $token=$this->authToken($auth_user);
          }

          return $token;

  }

  private function authToken(User $auth_user)
  {
    $user = new User;
    $user->name = $auth_user->name;
    $user->email = $auth_user->email;
    $user->password = bcrypt("");
    $user->save();
    $jwt_token = null;
    if (!$jwt_token = JWTAuth::fromUser($user)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid Email or Password',
        ], 401);
    }

    return response()->json([
        'success' => true,
        'token' => $jwt_token,
    ]);

    }

}
