<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Ads OAuth (covers CTWA, Facebook, Instagram)
    |--------------------------------------------------------------------------
    | Register a redirect URI in your Meta App dashboard:
    |   {APP_URL}/ads/oauth/meta/callback
    |
    | Required permissions:
    |   ads_management, ads_read, pages_show_list,
    |   instagram_basic, instagram_content_publish
    */
    'meta' => [
        'app_id'       => env('META_ADS_APP_ID', ''),
        'app_secret'   => env('META_ADS_APP_SECRET', ''),
        'redirect_uri' => env('META_ADS_REDIRECT_URI', ''),
        'scopes'       => 'ads_management,ads_read,pages_show_list,pages_read_engagement,instagram_basic,instagram_content_publish,business_management',
    ],

    /*
    |--------------------------------------------------------------------------
    | LinkedIn Marketing OAuth
    |--------------------------------------------------------------------------
    | Register redirect URI in your LinkedIn Developer App:
    |   {APP_URL}/ads/oauth/linkedin/callback
    |
    | Required permissions: rw_organization_admin, r_organization_social, r_ads, w_member_social
    */
    'linkedin' => [
        'client_id'     => env('LINKEDIN_ADS_CLIENT_ID', ''),
        'client_secret' => env('LINKEDIN_ADS_CLIENT_SECRET', ''),
        'redirect_uri'  => env('LINKEDIN_ADS_REDIRECT_URI', ''),
        'scopes'        => 'rw_organization_admin r_organization_social r_ads w_member_social',
    ],

];
