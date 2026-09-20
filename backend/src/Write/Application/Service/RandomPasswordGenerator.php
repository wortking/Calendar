<?php

declare(strict_types=1);

namespace App\Write\Application\Service;

/**
 * Contraseña aleatoria para cuentas creadas por un admin (que ya no elige
 * la contraseña él mismo, ver RegisterUserCommandHandler): siempre cumple
 * la misma regla de complejidad que se valida en Register/ChangePassword/
 * ResetPassword (mayúscula, dígito y carácter especial), así nunca puede
 * fallar esa validación aunque se reutilizara ahí.
 */
class RandomPasswordGenerator
{
    private const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const LOWER = 'abcdefghijkmnpqrstuvwxyz';
    private const DIGITS = '23456789';
    private const SPECIAL = '!@#$%^&*-_=+?';

    public function generate(int $length = 12): string
    {
        $length = max($length, 8);
        $all = self::UPPER . self::LOWER . self::DIGITS . self::SPECIAL;

        $password = self::UPPER[random_int(0, strlen(self::UPPER) - 1)]
            . self::DIGITS[random_int(0, strlen(self::DIGITS) - 1)]
            . self::SPECIAL[random_int(0, strlen(self::SPECIAL) - 1)];

        for ($i = strlen($password); $i < $length; ++$i) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
