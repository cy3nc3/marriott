<?php

namespace App\Services;

use Illuminate\Http\Request;

class HandheldDeviceDetector
{
    /**
     * Determine whether the current request likely came from a phone or tablet.
     */
    public function isHandheldRequest(Request $request): bool
    {
        $mobileHint = strtolower((string) $request->header('Sec-CH-UA-Mobile'));
        if ($mobileHint === '?1') {
            return true;
        }

        $platformHint = strtolower((string) $request->header('Sec-CH-UA-Platform'));
        if (
            str_contains($platformHint, 'android')
            || str_contains($platformHint, 'ios')
            || str_contains($platformHint, 'ipados')
        ) {
            return true;
        }

        $userAgent = strtolower((string) $request->userAgent());
        if ($userAgent === '') {
            return false;
        }

        return preg_match(
            '/android|iphone|ipad|ipod|mobile|tablet|kindle|silk|playbook|blackberry|opera mini|iemobile/',
            $userAgent
        ) === 1;
    }
}
