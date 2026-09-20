<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tipos de actividad (ej. "Body Pump", "Atención al público"): cada uno
 * pertenece siempre a un departamento. Un turno puede etiquetarse con uno,
 * validando que el usuario destino realmente pertenezca a ese departamento.
 */
final class Version20260919150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tipos de actividad por departamento + shifts.activity_type_id + permisos activity_types.manage/view.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_types (id UUID NOT NULL, department_id UUID NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(7) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_activity_types_department_id ON activity_types (department_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_activity_types_department_name ON activity_types (department_id, name)');

        $this->addSql('ALTER TABLE shifts ADD activity_type_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_shifts_activity_type_id ON shifts (activity_type_id)');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-00000000010b', 'activity_types.manage', 'Gestionar tipos de actividad'),
            ('00000000-0000-7000-8000-00000000010c', 'activity_types.view', 'Ver el listado de tipos de actividad')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-00000000010b'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-00000000010c'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-00000000010c')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id IN ('00000000-0000-7000-8000-00000000010b', '00000000-0000-7000-8000-00000000010c')");
        $this->addSql("DELETE FROM permissions WHERE id IN ('00000000-0000-7000-8000-00000000010b', '00000000-0000-7000-8000-00000000010c')");
        $this->addSql('DROP INDEX idx_shifts_activity_type_id');
        $this->addSql('ALTER TABLE shifts DROP activity_type_id');
        $this->addSql('DROP TABLE activity_types');
    }
}
