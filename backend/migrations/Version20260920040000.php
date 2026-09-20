<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Actividades de sala: una franja horaria puntual dentro de una sala (ej.
 * "Body Pump" en Sala 1 de 10:00 a 11:00), asignada a un empleado que ya
 * tenga un turno que cubra ese horario. Se gestionan desde el calendario
 * propio de cada sala (Admin y Coordinador, sin acotar por departamento: la
 * sala es compartida por todos). Aparecen luego "dentro" del turno del
 * empleado en sus propias consultas de turnos.
 */
final class Version20260920040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla room_activities + permiso room_activities.manage (Admin y Coordinador).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE room_activities (id UUID NOT NULL, room_id UUID NOT NULL, activity_type_id UUID NOT NULL, user_id UUID NOT NULL, start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_room_activities_room_id ON room_activities (room_id)');
        $this->addSql('CREATE INDEX idx_room_activities_user_id ON room_activities (user_id)');
        $this->addSql('CREATE INDEX idx_room_activities_start_at ON room_activities (start_at)');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-00000000010f', 'room_activities.manage', 'Gestionar actividades de sala')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-00000000010f'),
            ('00000000-0000-7000-8000-000000000003', '00000000-0000-7000-8000-00000000010f')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id = '00000000-0000-7000-8000-00000000010f'");
        $this->addSql("DELETE FROM permissions WHERE id = '00000000-0000-7000-8000-00000000010f'");
        $this->addSql('DROP TABLE room_activities');
    }
}
