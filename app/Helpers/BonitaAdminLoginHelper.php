<?php

namespace App\Helpers;

use App\Helpers\URLHelper;
use Illuminate\Support\Facades\Http;

class BonitaAdminLoginHelper
{
    /**
     * Obtener headers
     *
     */
    public function login()
    {
        $urlHelper = new URLHelper();
        $apiLoginUrl = $urlHelper->getBonitaEndpointURL('/loginservice');

        return Http::asForm()->post($apiLoginUrl, [
            'username' => config('services.bonita.admin_user'),
            'password' => config('services.bonita.admin_password'),
            'redirect' => 'false',
        ]);
    }
}
