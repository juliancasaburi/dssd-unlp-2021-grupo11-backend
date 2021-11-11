<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class BonitaAdminLoginHelper
{
    /**
     * Obtener headers
     *
     */
    public function login()
    {
        $apiLoginUrl = URLHelper::getBonitaEndpointURL('/loginservice');

        return Http::asForm()->post($apiLoginUrl, [
            'username' => config('services.bonita.admin_user'),
            'password' => config('services.bonita.admin_password'),
            'redirect' => 'false',
        ]);
    }
}
