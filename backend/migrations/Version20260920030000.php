<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Revierte la actividad/sala a nivel de turno completo: el turno vuelve a
 * ser solo horario + usuario. A partir de ahora las actividades son
 * puntuales, agendadas en el calendario de cada sala (ver tabla
 * room_activities de la migración siguiente) y ya no necesitan ocupar todo
 * el turno. Se borran también los tipos de actividad "Sin especificar"
 * autogenerados como relleno para los turnos que no tenían actividad
 * (backfill de Version20260920020000), que ya no tienen sentido.
 */
final class Version20260920030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'shifts vuelve a ser solo horario+usuario (se quita activity_type_id y room_id).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_shifts_activity_type_id');
        $this->addSql('DROP INDEX idx_shifts_room_id');
        $this->addSql('ALTER TABLE shifts DROP COLUMN activity_type_id');
        $this->addSql('ALTER TABLE shifts DROP COLUMN room_id');
        $this->addSql("DELETE FROM activity_types WHERE name = 'Sin especificar'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shifts ADD activity_type_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE shifts ADD room_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_shifts_activity_type_id ON shifts (activity_type_id)');
        $this->addSql('CREATE INDEX idx_shifts_room_id ON shifts (room_id)');
    }
}
