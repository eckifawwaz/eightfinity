<?php

namespace App\Support;

use Illuminate\Http\Request;

class PortalUrl
{
    public static function base(string $portal): ?string
    {
        $url = config("portals.{$portal}_url");

        return $url ? rtrim($url, '/') : null;
    }

    public static function to(string $portal, string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');
        $baseUrl = self::base($portal);

        return $baseUrl ? $baseUrl.$path : $path;
    }

    public static function matches(Request $request, string $portal): bool
    {
        $baseUrl = self::base($portal);

        if (! $baseUrl) {
            return true;
        }

        return strcasecmp($request->getSchemeAndHttpHost(), $baseUrl) === 0;
    }
}
