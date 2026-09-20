<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed de roles y permisos base (RBAC). Sin esto, RegisterUserCommandHandler
 * no tiene ningún "ROLE_USER" que asignar a los usuarios nuevos.
 */
final class Version20260919100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed: roles y permisos base (ROLE_USER, ROLE_ADMIN, users.*, roles.manage, permissions.manage).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO roles (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000001', 'ROLE_USER', 'Usuario estándar'),
            ('00000000-0000-7000-8000-000000000002', 'ROLE_ADMIN', 'Administrador de la aplicación')");

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000101', 'users.view', 'Ver otros usuarios'),
            ('00000000-0000-7000-8000-000000000102', 'users.edit', 'Editar otros usuarios'),
            ('00000000-0000-7000-8000-000000000103', 'users.delete', 'Eliminar otros usuarios'),
            ('00000000-0000-7000-8000-000000000104', 'roles.manage', 'Gestionar roles'),
            ('00000000-0000-7000-8000-000000000105', 'permissions.manage', 'Gestionar permisos')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000101'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000102'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000103'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000104'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000105'),
            ('00000000-0000-7000-8000-000000000001', '00000000-0000-7000-8000-000000000101')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM role_permissions');
        $this->addSql('DELETE FROM permissions');
        $this->addSql('DELETE FROM roles');
    }
}
