<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;
use App\Helpers\URLHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Helpers\BonitaRequestHelper;

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
        $urlHelper = new URLHelper();
        $apiLoginUrl = $urlHelper->getBonitaEndpointURL('/loginservice');

        $bonitaLoginResponse = Http::asForm()->post($apiLoginUrl, [
            'username' => config('services.bonita.admin_user'),
            'password' => config('services.bonita.admin_password'),
            'redirect' => 'false',
        ]);

        $jsessionid = $bonitaLoginResponse->cookies()->toArray()[1]['Value'];
        $xBonitaAPIToken = $bonitaLoginResponse->cookies()->toArray()[2]['Value'];
        $bonitaRequestHelper = new BonitaRequestHelper();
        $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
        $apiIdentityUsersUrl = $urlHelper->getBonitaEndpointURL('/API/identity/user?p=0');

        $users = Http::withHeaders($bonitaAuthHeaders)->get($apiIdentityUsersUrl);
        
        foreach (json_decode($users, true) as $user) {
            $role = '';
            if (Str::contains($user["userName"], 'admin')) {
                $role = 'admin';
            } elseif (Str::contains($user["userName"], 'apoderado')) {
                $role = 'apoderado';
            } elseif (Str::contains($user["userName"], 'empleado')) {
                $role = 'empleado-mesa-de-entradas';
            } elseif (Str::contains($user["userName"], 'escribano')) {
                $role = 'escribano-area-legales';
            }
            if ($role != ''){
                User::create([
                    'name'      =>  $user["firstname"],
                    'email'     =>  $user["userName"],
                    'password'  =>  bcrypt('grupo11'),
                    'bonita_user_id'  =>  $user["id"],
                    'email_verified_at' => Carbon::now()
                ])->assignRole($role);
            }
        }
    }
}
