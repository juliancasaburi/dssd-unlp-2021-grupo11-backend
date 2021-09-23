<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Get a JWT and cookie via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $credentials['email'])->first();
        $username = $user->name;

        try {
            $apiUrl = env('BONITA_API_URL') . '/loginservice';
            $response = Http::asForm()->post($apiUrl, [
                'username' => $username,
                'password' => $credentials['password'],
                'redirect' => 'false',
            ]);
            if ($response->status() == 401)
                return response()->json("401 Unauthorized", 401);

            return $this->respondWithTokenAndCookies($token, $response->cookies()->toArray());
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
            if (!$jsessionid || !$xBonitaAPIToken)
                return response()->json("No cookies set", 400);

            $apiUrl = env('BONITA_API_URL') . '/logoutservice';
            $response = Http::post($apiUrl);

            if ($response->status() == 401)
                return response()->json("401 Unauthorized", 401);

            auth('api')->logout();

            return response()->json("Logged out", 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @param  array $cookieArray
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithTokenAndCookies($token, $cookieArray)
    {
        $cookie = cookie($cookieArray[1]['Name'], $cookieArray[1]['Value'], 1440);
        $cookie2 = cookie($cookieArray[2]['Name'], $cookieArray[2]['Value'], 1440);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'JSESSIONID' => $cookieArray[1]['Value'],
            'X-Bonita-API-Token' => $cookieArray[2]['Value']
        ])->cookie($cookie)->cookie($cookie2);
    }

    /** TODO: Bonita Rest API register
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $apiLoginUrl = env('BONITA_API_URL') . '/loginservice';
            $bonitaLoginResponse = Http::asForm()->post($apiLoginUrl, [
                'username' => 'grupo11.admin',
                'password' => 'grupo11',
                'redirect' => 'false',
            ]);
            if ($bonitaLoginResponse->status() == 401)
                return response()->json("500 Internal Server Error", 500);

            $apiRegisterUrl = env('BONITA_API_URL') . '/API/identity/user';

            $jsessionid = $bonitaLoginResponse->cookies()->toArray()[1]['Value'];
            $xBonitaAPIToken = $bonitaLoginResponse->cookies()->toArray()[2]['Value'];

            /* Register Bonita User */
            $bonitaRegisterResponse = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'Accept' => 'application/json',
                'X-Bonita-API-Token' => $xBonitaAPIToken,
                'Content-Type' => 'application/json'
            ])->post($apiRegisterUrl, [
                "userName" => $request->email,
                "password" => $request->password,
                "password_confirm" => $request->password,
                "icon" => "",
                "firstname" => $request->name,
                "lastname" => $request->name,
                "title" => "Mr",
                "job_title" => "Apoderado",
                "manager_id" => "1",
            ]);

            if ($bonitaRegisterResponse->status() != 200)
                return response()->json($bonitaRegisterResponse->json(), $bonitaRegisterResponse->status());

            /* Enable Bonita User */
            $bonitaEnableUserUrl = env('BONITA_API_URL') . '/API/identity/user/'.$bonitaRegisterResponse['id'];
            $bonitaEnableUserResponse = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'Accept' => 'application/json',
                'X-Bonita-API-Token' => $xBonitaAPIToken,
                'Content-Type' => 'application/json'
            ])->put($bonitaEnableUserUrl, [
                "enabled" => "true",
            ]);

            if ($bonitaEnableUserResponse->status() != 200)
                return response()->json($bonitaRegisterResponse->json(), $bonitaRegisterResponse->status());

            $bonitaUserData = $bonitaRegisterResponse->json();
            $bonitaUserData ['enabled'] = "true";

            /* Save Eloquent model instance */
            $user = User::create(array_merge(
                $validator->validated(),
                ['password' => bcrypt($request->password)]
            ));

            /* Return response */
            return response()->json([
                'message' => 'User successfully registered',
                'api-user' => $user,
                'bonita-user' => $bonitaUserData,
            ], 201);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
