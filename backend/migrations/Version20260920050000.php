<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baja lógica de usuarios: nunca se borra el registro físicamente. Un
 * usuario dado de baja no puede volver a iniciar sesión (chequeado en
 * App\Shared\Infrastructure\Security\UserChecker) ni con una sesión/JWT ya
 * emitido antes de la baja. Permiso "users.deactivate" para Admin y
 * Coordinador (a diferencia de "users.delete", que queda solo para Admin).
 */
final class Version20260920050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baja lógica de usuarios (deactivated_at/deactivated_by) + permiso users.deactivate.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD deactivated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD deactivated_by UUID DEFAULT NULL');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000110', 'users.deactivate', 'Dar de baja a otros usuarios')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000110'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-000000000110')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id = '00000000-0000-7000-8000-000000000110'");
        $this->addSql("DELETE FROM permissions WHERE id = '00000000-0000-7000-8000-000000000110'");
        $this->addSql('ALTER TABLE users DROP COLUMN deactivated_by');
        $this->addSql('ALTER TABLE users DROP COLUMN deactivated_at');
    }
}
