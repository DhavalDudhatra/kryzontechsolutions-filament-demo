<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings cache
    |--------------------------------------------------------------------------
    |
    | Global two-factor settings are stored in the `settings` table and cached
    | under this key. Saving the settings page forgets the key, so changes are
    | picked up on the next request without a manual cache clear.
    |
    */

    'cache_key' => 'two_factor_settings',

    /*
    |--------------------------------------------------------------------------
    | Lockout protection
    |--------------------------------------------------------------------------
    |
    | The role that must always keep a way into the panel. The last remaining
    | member of this role may disable or reset two-factor authentication, but
    | a reset switches their requirement off so the forced enrolment screen
    | cannot lock the organisation out of global settings management.
    |
    */

    'protected_role' => 'super_admin',

];
