<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

/**
 * Claves de caché compartidas entre el lado que lee (guarda en caché)
 * y el lado que escribe (invalida), para que no puedan desincronizarse.
 */
final class CacheKeys
{
    public const TTL_SECONDS = 60;

    public static function bicycleComponents(string $bicycleId): string
    {
        return 'bicycle_components_' . $bicycleId;
    }

    public static function bicycleDetail(string $bicycleId): string
    {
        return 'bicycle_detail_' . $bicycleId;
    }

    public static function userAddresses(string $userId): string
    {
        return 'user_addresses_' . $userId;
    }

    public static function userImages(string $userId): string
    {
        return 'user_images_' . $userId;
    }

    public static function userRoles(string $userId): string
    {
        return 'user_roles_' . $userId;
    }

    public static function jwtBlocklist(string $jti): string
    {
        return 'jwt_blocklist_' . $jti;
    }
}
