<?php

namespace App\Helpers;

use GuzzleHttp\Client as GuzzleClient;


class BonitaMembershipHelper
{
    /**
     * Obtener id del grupo por nombre.
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string  $groupName
     * @return string
     */
    public static function groupIdByName($jsessionid, $xBonitaAPIToken, $groupName)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/identity/group?p=0&f=name%3d' . $groupName);

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

        $client = new GuzzleClient([
            'headers' => $bonitaAuthHeaders
        ]);

        $response = $client->request('GET', $url);
        $response_body = json_decode($response->getBody()->getContents(), true);

        return head($response_body)["id"];
    }

    /**
     * Obtener id del rol por nombre.
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string  $roleName
     * @return string
     */
    public static function roleIdByName($jsessionid, $xBonitaAPIToken, $roleName)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/identity/role?p=0&f=name%3d' . $roleName);

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

        $client = new GuzzleClient([
            'headers' => $bonitaAuthHeaders
        ]);

        $response = $client->request('GET', $url);
        $response_body = json_decode($response->getBody()->getContents(), true);

        return head($response_body)["id"];
    }
}
