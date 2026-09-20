<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Turnos de trabajo: un turno es un evento puntual (fecha + hora de inicio y
 * fin) asignado a un usuario. Tabla "shifts" + el permiso "shifts.manage" que
 * ya exigen CreateShiftController/DeleteShiftController vía #[IsGranted].
 */
final class Version20260919120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Turnos: tabla shifts + permiso shifts.manage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shifts (id UUID NOT NULL, user_id UUID NOT NULL, start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_shifts_user_id ON shifts (user_id)');
        $this->addSql('CREATE INDEX idx_shifts_start_at ON shifts (start_at)');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000107', 'shifts.manage', 'Gestionar turnos de trabajo')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000107')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id = '00000000-0000-7000-8000-000000000107'");
        $this->addSql("DELETE FROM permissions WHERE id = '00000000-0000-7000-8000-000000000107'");
        $this->addSql('DROP TABLE shifts');
    }
}
