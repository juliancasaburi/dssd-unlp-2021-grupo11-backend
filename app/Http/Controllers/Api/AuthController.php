<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;
use App\Models\User;
use App\Helpers\BonitaMembershipHelper;
use App\Helpers\BonitaUserHelper;
use App\Http\Resources\User as UserResource;

class AuthController extends Controller
{
    /**
     * Get a JWT and cookie via given credentials.
     *
     * @OA\Post(
     *    path="/api/auth/login",
     *    summary="Login",
     *    description="Login con email y password",
     *    operationId="authLogin",
     *    tags={"auth"},
     *    @OA\RequestBody(
     *       @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *             type="object", 
     *             @OA\Property(
     *                property="email",
     *                type="string",
     *             ),
     *             @OA\Property(
     *                property="password",
     *                type="string",
     *             ),
     *          ),
     *      )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Succesful login",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="401 Unauthorized",
     *       @OA\JsonContent(
     *          example={"error":"Unauthorized"}
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * )
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $urlHelper = new URLHelper();
            $apiUrl = $urlHelper->getBonitaEndpointURL('/loginservice');

            $response = Http::asForm()->post($apiUrl, [
                'username' => $credentials["email"],
                'password' => $credentials['password'],
                'redirect' => 'false',
            ]);
            if ($response->status() == 401)
                return response()->json("401 Unauthorized", 401);

            $user = new UserResource(auth('api')->user());

            return $this->respondWithTokenCookiesAndUser($token, $response->cookies()->toArray(), $user);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @OA\Post(
     *    path="/api/auth/logout",
     *    summary="Logout",
     *    description="Logout",
     *    operationId="authLogout",
     *    tags={"auth"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Response(
     *       response=200,
     *       description="Success"
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     * )
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            $urlHelper = new URLHelper();
            $apiUrl = $urlHelper->getBonitaEndpointURL('/logoutservice');

            $response = Http::post($apiUrl);

            if ($response->status() == 401)
                return response()->json("401 Unauthorized", 401);

            auth('api')->logout();

            return response()->json(['message' => 'Successfully logged out'], 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @param  array $cookieArray
     * @param  User $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithTokenCookiesAndUser($token, $cookieArray, $user)
    {
        $cookie = cookie($cookieArray[1]['Name'], $cookieArray[1]['Value'], 1440);
        $cookie2 = cookie($cookieArray[2]['Name'], $cookieArray[2]['Value'], 1440);

        return response()->json(["auth" => [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'JSESSIONID' => $cookieArray[1]['Value'],
            'X-Bonita-API-Token' => $cookieArray[2]['Value']
        ], "user" => $user])->cookie($cookie)->cookie($cookie2);
    }

    /** Register a User
     *
     * @OA\Post(
     *    path="/api/auth/register",
     *    summary="Register",
     *    description="Register con name, email, password y password_confirmation",
     *    operationId="authLogin",
     *    tags={"auth"},
     *    @OA\RequestBody(
     *       @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *             type="object", 
     *             @OA\Property(
     *                property="name",
     *                type="string",
     *             ),
     *              @OA\Property(
     *                property="email",
     *                type="string",
     *             ),
     *             @OA\Property(
     *                property="password",
     *                type="string",
     *             ),
     *              @OA\Property(
     *                property="password_confirmation",
     *                type="string",
     *             ),
     *          ),
     *      )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Succesful register",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="400 Bad Request",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * )
     * 
     * @param  \Illuminate\Http\Request $request
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
            return response()->json($validator->errors(), 400);
        }

        try {
            $urlHelper = new URLHelper();
            $apiLoginUrl = $urlHelper->getBonitaEndpointURL('/loginservice');

            $bonitaLoginResponse = Http::asForm()->post($apiLoginUrl, [
                'username' => config('services.bonita.admin_user'),
                'password' => config('services.bonita.admin_password'),
                'redirect' => 'false',
            ]);
            if ($bonitaLoginResponse->status() == 401)
                return response()->json("500 Internal Server Error", 500);

            $jsessionid = $bonitaLoginResponse->cookies()->toArray()[1]['Value'];
            $xBonitaAPIToken = $bonitaLoginResponse->cookies()->toArray()[2]['Value'];

            $bonitaMembershipHelper = new BonitaMembershipHelper();
            $apoderadoGroupId = $bonitaMembershipHelper->groupIdByName($jsessionid, $xBonitaAPIToken, "Apoderado");
            $apoderadoRoleId = $bonitaMembershipHelper->roleIdByName($jsessionid, $xBonitaAPIToken, "apoderado");

            /* Register Bonita User */
            $bonitaUserHelper = new BonitaUserHelper();
            $bonitaRegisterResponse = $bonitaUserHelper->registerUser($jsessionid, $xBonitaAPIToken, $validator->validated());
            if ($bonitaRegisterResponse->status() != 200)
                return response()->json($bonitaRegisterResponse->json(), $bonitaRegisterResponse->status());

            /* Set Bonita Membership */
            $bonitaUserData = $bonitaRegisterResponse->json();
            $bonitaMemberShipRequestData = [
                "user_id" => $bonitaUserData["id"],
                "group_id" => $apoderadoGroupId,
                "role_id" => $apoderadoRoleId,
            ];
            $bonitaSetUserMembershipResponse = $bonitaUserHelper->setUserMembership($jsessionid, $xBonitaAPIToken, $bonitaMemberShipRequestData);
            if ($bonitaSetUserMembershipResponse->status() != 200)
                return response()->json($bonitaSetUserMembershipResponse->json(), $bonitaSetUserMembershipResponse->status());

            /* Enable Bonita User */
            $bonitaEnableUserResponse = $bonitaUserHelper->enableUser($jsessionid, $xBonitaAPIToken, $bonitaRegisterResponse['id']);
            if ($bonitaEnableUserResponse->status() != 200)
                return response()->json($bonitaEnableUserResponse->json(), $bonitaEnableUserResponse->status());
            $bonitaUserData['enabled'] = "true";

            /* Save Eloquent model instance */
            $user = User::create(array_merge(
                $validator->validated(),
                [
                    'password' => bcrypt($request->password),
                    'bonita_user_id' => $bonitaUserData['id'],
                ]
            ));
            $user->assignRole('apoderado'); // Assign spatie/laravel-permission user role

            /* Return response */
            return response()->json([
                'message' => 'Usuario registrado exitosamente.',
                'api-user' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
