<?php

namespace App\Helpers;

use GuzzleHttp\Client as GuzzleClient;
use App\Helpers\URLHelper;


class BonitaMembershipHelper
{
    /**
     * Obtener id del grupo por nombre.
     *
     * @param Illuminate\Http\Request $request
     * @param  string  $groupName
     * @return string
     */
    public function groupIdByName($request, $groupName)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

        $urlHelper = new URLHelper();
        $url = $urlHelper->getBonitaEndpointURL('/API/identity/group?p=0&f=name%3d' . $groupName);

        $headers = [
            'Content-Type' => 'application/json',
            'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
            'X-Bonita-API-Token' => $xBonitaAPIToken,
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);

        $response = $client->request('GET', $url);
        $response_body = json_decode($response->getBody()->getContents(), true);

        return $response_body[0]["id"];
    }

    /**
     * Obtener id del rol por nombre.
     *
     * @param Illuminate\Http\Request $request
     * @param  string  $roleName
     * @return string
     */
    public function roleIdByName($request, $roleName)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

        $urlHelper = new URLHelper();
        $url = $urlHelper->getBonitaEndpointURL('/API/identity/role?p=0&f=name%3d' . $roleName);

        $headers = [
            'Content-Type' => 'application/json',
            'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
            'X-Bonita-API-Token' => $xBonitaAPIToken,
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);

        $response = $client->request('GET', $url);
        $response_body = json_decode($response->getBody()->getContents(), true);

        return $response_body[0]["id"];
    }
}
