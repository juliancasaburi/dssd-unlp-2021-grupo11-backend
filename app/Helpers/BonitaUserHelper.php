<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;
use App\Helpers\BonitaRequestHelper;

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
    public function registerUser($jsessionid, $xBonitaAPIToken, $userData)
    {
        try {
            $urlHelper = new URLHelper();
            $bonitaRegisterUserUrl = $urlHelper->getBonitaEndpointURL("/API/identity/user");

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
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
    public function setUserMembership($jsessionid, $xBonitaAPIToken, $data)
    {
        try {
            $urlHelper = new URLHelper();
            $bonitaSetUserMembershipUrl = $urlHelper->getBonitaEndpointURL("/API/identity/membership");

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
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
    public function enableUser($jsessionid, $xBonitaAPIToken, $bonitaUserId)
    {
        try {
            $urlHelper = new URLHelper();
            $bonitaEnableUserUrl = $urlHelper->getBonitaEndpointURL("/API/identity/user/{$bonitaUserId}");

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
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
