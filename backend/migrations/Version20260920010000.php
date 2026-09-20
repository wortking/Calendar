<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Salas (ej. "Sala 1", "Estudio A"): pertenecen siempre a una empresa
 * (igual que un departamento), pero una empresa puede no tener ninguna. Un
 * tipo de actividad puede opcionalmente desarrollarse en una sala; la
 * validación de que esa sala no la use otra actividad al mismo horario se
 * hace en el handler de turnos, no acá.
 */
final class Version20260920010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Salas por empresa + activity_types.room_id + permisos rooms.manage/view.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rooms (id UUID NOT NULL, company_id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_rooms_company_id ON rooms (company_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rooms_company_name ON rooms (company_id, name)');

        $this->addSql('ALTER TABLE activity_types ADD room_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_activity_types_room_id ON activity_types (room_id)');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-00000000010d', 'rooms.manage', 'Gestionar salas'),
            ('00000000-0000-7000-8000-00000000010e', 'rooms.view', 'Ver el listado de salas')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-00000000010d'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-00000000010e'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-00000000010e')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id IN ('00000000-0000-7000-8000-00000000010d', '00000000-0000-7000-8000-00000000010e')");
        $this->addSql("DELETE FROM permissions WHERE id IN ('00000000-0000-7000-8000-00000000010d', '00000000-0000-7000-8000-00000000010e')");
        $this->addSql('DROP INDEX idx_activity_types_room_id');
        $this->addSql('ALTER TABLE activity_types DROP room_id');
        $this->addSql('DROP TABLE rooms');
    }
}
