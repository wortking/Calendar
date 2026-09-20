<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Un usuario creado por un admin recibe una contraseña aleatoria (nunca la
 * elige el propio admin) y queda marcado para tener que cambiarla en su
 * primer inicio de sesión. El flag se limpia solo (ver User::changePassword)
 * cuando el usuario efectivamente cambia su contraseña, ya sea desde "Cambiar
 * contraseña" o recuperándola por "olvidé mi contraseña".
 */
final class Version20260920060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Columna must_change_password en users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD must_change_password BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN must_change_password');
    }
}
