<?php

namespace App\Helpers;

class URLHelper
{
    /**
     * Obtener endpoint de Bonita REST API.
     *
     * @param  string $endpointName
     * @return string
     */
    public function getBonitaEndpointURL(string $endpointName)
    {
        return "{env('BONITA_API_URL')}{$endpointName}";
    }
}
diff --git a/app/Helpers/BonitaProcessHelper.php b/app/Helpers/BonitaProcessHelper.php
