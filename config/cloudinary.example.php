<?php
// Copy to cloudinary.local.php and enter your Cloudinary API credentials.
// Keep cloudinary.local.php outside GitHub and outside the web document root.
return [
    'cloud_name' => 'YOUR_CLOUD_NAME',
    'api_key' => 'YOUR_API_KEY',
    'api_secret' => 'YOUR_API_SECRET',
    // Set true if your local network cannot reach Cloudinary through IPv6.
    'force_ipv4' => true,
    // Optional. Usually leave empty: PHP uses your configured CA trust store.
    // Set to the local CA bundle path if PHP cURL reports SSL error 60.
    'ca_bundle' => '',
];
