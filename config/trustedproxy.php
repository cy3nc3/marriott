<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of reverse proxy IPs / CIDRs allowed to set
    | forwarded headers. Keep null for local development and set explicitly
    | in production deployments.
    |
    */
    'proxies' => (function (): ?array {
        $configured = env('TRUSTED_PROXIES');

        if (! is_string($configured) || trim($configured) === '') {
            return null;
        }

        $proxies = array_values(array_filter(array_map('trim', explode(',', $configured))));

        return $proxies === [] ? null : $proxies;
    })(),
];
