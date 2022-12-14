<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class AuthController extends Controller
{

    public function register(Request $request) {

        $validator = Validator::make($request->all(), [
            'password' => 'bail|required|min:6',
            'email' => 'bail|required|email|unique:users',
        ]);


        if ($validator->fails()) {
            return response([
                'message' => $validator->messages()->first()
            ], 422);
        }

        // $check = User::where('ip_address', $request->ip())->first();

        // if ($check) return response(['message' => 'Mỗi user chỉ được đang ký một tài khoản'], 422);

        $data = $request->all();
        // $data['ip_address'] = $request->ip();

        $user = User::create($data);

        auth()->login($user);

        $token = $user->createToken($user->id)->plainTextToken;


        return $this->respondWithToken($token);

    }

    public function login (Request $request) {
        $credentials = ['email' => $request->email, 'password' => $request->password];

        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $abilities = auth()->user()->id === 1 ? ['order:list-all'] : [];
        $token = auth()->user()->createToken(auth()->user()->id, $abilities)->plainTextToken;

        return $this->respondWithToken($token);
    }

    public function me(Request $request) {

        return response([
            'user' => auth()->user(),
            'access_token' => $request->bearerToken()
        ]);
    }

    public function changePassword(Request $request) {

        $validator = Validator::make($request->all(), [
            'password' => 'bail|required|confirmed',
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->messages()->first()
            ], 422);
        }

        $user = User::find(auth()->user()->id);

        if ($user) {
            $user->update(['password' => $request->password]);
            return 1;
        }

        return response([
            'message' => 'K thể thay đổi mk'
        ], 422);
    }

    public function refresh () {
        return $this->respondWithToken(auth()->refresh());
    }

    public function logout () {
        auth()->user()->tokens()->delete();
        return [
            'message' => 'Logout Successfull'
        ];
    }

    protected function respondWithToken($token)
    {

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => time() + 3600,
            'user' =>  auth()->user()
        ]);
    }

    public function socialLogin (Request $request) {

        $email = $request->email ?  $request->email : null;
        $name = $request->name ? $request->name : null;
        $picture = $request->picture ? $request->picture : null;

        $user = User::where('email', $email)->first();

        if ($user) {
            auth()->login($user);

            $token = $user->createToken($user->id)->plainTextToken;

            return $this->respondWithToken($token);
        }
        else {
            $user = User::create(['name' =>  $name, 'email' => $email, 'picture' => $picture, 'password' => 'social_login']);

            if ($user) {
                auth()->login($user);

                $token = $user->createToken($user->id)->plainTextToken;

                return $this->respondWithToken($token);
            }
        }

        return response(['message' => 'Unprocess Entity'], 422);
    }
}
