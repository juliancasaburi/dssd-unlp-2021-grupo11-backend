<?php

namespace App\Providers;

class GoogleDriveServiceProvider extends \Illuminate\Support\ServiceProvider{ // can be a custom ServiceProvider
    // ...
    public function boot(){
        // ...
        try{
            \Storage::extend('google', function($app, $config) {
                $options = []; 
                if(!empty($config['teamDriveId']??null)) $options['teamDriveId']=$config['teamDriveId']; 
                $client = new \Google_Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);
                $service = new \Google_Service_Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service,$config['folder']??'/', $options);
                return new \League\Flysystem\Filesystem($adapter);
            });
        }catch(\Exception $e){  }
        // ...
    }
    // ...
}
