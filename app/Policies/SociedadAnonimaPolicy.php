<?php

namespace App\Policies;

use App\Models\SociedadAnonima;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SociedadAnonimaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SociedadAnonima  $sociedadAnonima
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SociedadAnonima $sociedadAnonima)
    {
        return $sociedadAnonima->created_by == $user->id;
    }

    /**
     * Determine whether the user can patch the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SociedadAnonima  $sociedadAnonima
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function patch(User $user, SociedadAnonima $sociedadAnonima)
    {
        return ($sociedadAnonima->estado_evaluacion == 'Rechazado por empleado-mesa-de-entradas'
            and $sociedadAnonima->created_by == $user->id);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SociedadAnonima  $sociedadAnonima
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SociedadAnonima $sociedadAnonima)
    {
        return ($sociedadAnonima->estado_evaluacion == 'Rechazado por escribano-area-legales'
            and $sociedadAnonima->created_by == $user->id);
    }
}
