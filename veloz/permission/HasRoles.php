<?php

namespace Veloz\Permission;

class HasRoles
{
    public function hasRole($role): bool
    {
        // return auth()->user('role') === $role;
    }
}