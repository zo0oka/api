<?php
 
namespace App\Http\Controllers;
 
use App\Http\Requests\RegisterAuthRequest;
use App\User;
use Illuminate\Http\Request;
use JWTAuth;
use Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;
 
class ApiController extends Controller
{
    public $loginAfterSignUp = true;
    public function redirectToFacebookProvider()
    {
        return Socialite::driver('facebook')->redirect();
    }
    public function handleProviderFacebookCallback()
    {

        $auth_user = Socialite::driver('facebook')->user();
        $existUser = User::where('email',$auth_user->email)->first();
        $token=$this->authToken($auth_user,$existUser);
        return $token;

    }
    public function register(RegisterAuthRequest $request)
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
 
    public function login(Request $request)
    {
        $input = $request->only('email', 'password');
        $jwt_token = null;
 
        if (!$jwt_token = JWTAuth::attempt($input)) {
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


    public function redirectToGoogleProvider()
  {
      return Socialite::driver('google')->redirect();
  }


  public function handleProviderGoogleCallback()
  {
  
          $auth_user = Socialite::driver('google')->user();
          $existUser = User::where('email',$auth_user->email)->first();
          $token=$this->authToken($auth_user,$existUser);
          return $token;

  }

  private function authToken(object $auth_user ,User $existUser)
  {
    if($existUser) {
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
        }
        else {

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

}