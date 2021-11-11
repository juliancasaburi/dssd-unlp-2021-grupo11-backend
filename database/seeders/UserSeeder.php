<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Helpers\BonitaAdminLoginHelper;
use App\Helpers\BonitaRequestHelper;
use App\Helpers\URLHelper;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Bonita users
        $bonitaAdminLoginHelper = new BonitaAdminLoginHelper();
        $bonitaAdminLoginResponse = $bonitaAdminLoginHelper->login();

        $jsessionid = $bonitaAdminLoginResponse->cookies()->toArray()[1]['Value'];
        $xBonitaAPIToken = $bonitaAdminLoginResponse->cookies()->toArray()[2]['Value'];
        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
        $apiIdentityUsersUrl = URLHelper::getBonitaEndpointURL('/API/identity/user?p=0&f=enabled=true');

        $users = Http::withHeaders($bonitaAuthHeaders)->get($apiIdentityUsersUrl);
        
        foreach (json_decode($users, true) as $user) {
            $userData = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get(URLHelper::getBonitaEndpointURL("/API/identity/user?s={$user['userName']}"));

            $userId = head($userData->json())['id'];

            $membershipData = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get(URLHelper::getBonitaEndpointURL("/API/identity/membership?p=0&c=10&f=user_id={$userId}&d=role_id"));

            if (!empty($membershipData->json())){
                User::create([
                    'name'      =>  $user["firstname"],
                    'email'     =>  $user["userName"],
                    'password'  =>  bcrypt('grupo11'),
                    'bonita_user_id'  =>  $user["id"],
                    'email_verified_at' => Carbon::now()
                ])->assignRole(head($membershipData->json())['role_id']["name"]);
            }
        }
    }
}
