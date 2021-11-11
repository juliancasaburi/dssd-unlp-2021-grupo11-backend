<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BonitaUserHelper
{
    /**
     * Registrar un usuario en Bonita
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  array $userData
     * @return \Illuminate\Http\JsonResponse
     */
    public static function registerUser($jsessionid, $xBonitaAPIToken, $userData)
    {
        try {
            $bonitaRegisterUserUrl = URLHelper::getBonitaEndpointURL("/API/identity/user");

            $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
            $headers = array_merge($bonitaAuthHeaders, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);

            /* Register Bonita User */
            $bonitaRegisterResponse = Http::withHeaders($headers)->post($bonitaRegisterUserUrl, [
                "userName" => $userData["email"],
                "email" => $userData["email"],
                "password" => $userData["password"],
                "password_confirm" => $userData["password"],
                "icon" => "",
                "firstname" => $userData["name"],
                "lastname" => $userData["name"],
                "title" => "Mr",
                "job_title" => "Apoderado",
            ]);

            return $bonitaRegisterResponse;
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Registrar una membership para un usuario
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setUserMembership($jsessionid, $xBonitaAPIToken, $data)
    {
        try {
            $bonitaSetUserMembershipUrl = URLHelper::getBonitaEndpointURL("/API/identity/membership");

            $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
            $headers = array_merge($bonitaAuthHeaders, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);

            /* Register Bonita User */
            $bonitaSetUserMembershipResponse = Http::withHeaders($headers)->post($bonitaSetUserMembershipUrl, [
                "user_id" => $data["user_id"],
                "group_id" => $data["group_id"],
                "role_id" => $data["role_id"],
            ]);

            return $bonitaSetUserMembershipResponse;
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }


    /**
     * Habilitar un usuario
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $bonitaUserId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function enableUser($jsessionid, $xBonitaAPIToken, $bonitaUserId)
    {
        try {
            $bonitaEnableUserUrl = URLHelper::getBonitaEndpointURL("/API/identity/user/{$bonitaUserId}");

            $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
            $headers = array_merge($bonitaAuthHeaders, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);
            
            $bonitaEnableUserResponse = Http::withHeaders($headers)->put($bonitaEnableUserUrl, [
                "enabled" => "true",
            ]);

            return $bonitaEnableUserResponse;
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
