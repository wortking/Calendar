<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Formaliza 3 roles con alcance: Admin (todo), Coordinador (nuevo — todo pero
 * acotado a su propio departamento, aplicado en los handlers vía
 * DepartmentScopeGuard) y Empleado (ROLE_USER — pierde "users.view", ya que
 * no debe poder listar a todos los usuarios).
 */
final class Version20260919130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rol ROLE_COORDINADOR + permiso departments.view + reparto de permisos por rol.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO roles (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000003', 'ROLE_COORDINADOR', 'Coordinador de un departamento')");

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000108', 'departments.view', 'Ver el listado de departamentos')");

        // Admin: además de lo que ya tenía, necesita departments.view porque
        // ListDepartmentsController pasa a exigir ese permiso en vez de
        // departments.manage (separa "ver" de "crear/editar").
        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000108')");

        // Coordinador: users.view/users.edit (gestionar gente de su depto),
        // shifts.manage (turnos de su depto), departments.view (para poder
        // elegir su propio departamento al asignarlo). Nada de
        // departments.manage, roles.manage, permissions.manage, users.delete.
        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-000000000101'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-000000000102'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-000000000107'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-000000000108')");

        // Empleado (ROLE_USER) ya no lista usuarios: solo ve su propio
        // calendario y el de sus compañeros vía /api/me/*.
        $this->addSql("DELETE FROM role_permissions WHERE role_id = '00000000-0000-7000-8000-000000000001' AND permission_id = '00000000-0000-7000-8000-000000000101'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000001', '00000000-0000-7000-8000-000000000101')");

        $this->addSql("DELETE FROM role_permissions WHERE role_id = '00000000-0000-7000-8000-000000000003'");
        $this->addSql("DELETE FROM role_permissions WHERE role_id = '00000000-0000-7000-8000-000000000002' AND permission_id = '00000000-0000-7000-8000-000000000108'");
        $this->addSql("DELETE FROM permissions WHERE id = '00000000-0000-7000-8000-000000000108'");
        $this->addSql("DELETE FROM roles WHERE id = '00000000-0000-7000-8000-000000000003'");
    }
}
