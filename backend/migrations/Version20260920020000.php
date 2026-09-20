<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

/**
 * Un turno siempre representa una actividad concreta: activity_type_id deja
 * de ser opcional. Los turnos existentes sin actividad (datos de prueba) se
 * migran a una actividad de relleno "Sin especificar" en el departamento
 * del usuario, para no perderlos.
 *
 * La sala deja de vivir en el tipo de actividad (la misma actividad puede
 * darse en salas distintas según el día) y pasa al turno: shifts.room_id.
 */
final class Version20260920020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'shifts.activity_type_id obligatorio + shifts.room_id (la sala pasa del tipo de actividad al turno).';
    }

    public function up(Schema $schema): void
    {
        $this->backfillMissingActivityTypes();

        $this->addSql('ALTER TABLE shifts ALTER COLUMN activity_type_id SET NOT NULL');

        $this->addSql('ALTER TABLE shifts ADD room_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_shifts_room_id ON shifts (room_id)');

        $this->addSql('DROP INDEX idx_activity_types_room_id');
        $this->addSql('ALTER TABLE activity_types DROP room_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_types ADD room_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_activity_types_room_id ON activity_types (room_id)');

        $this->addSql('DROP INDEX idx_shifts_room_id');
        $this->addSql('ALTER TABLE shifts DROP room_id');

        $this->addSql('ALTER TABLE shifts ALTER COLUMN activity_type_id DROP NOT NULL');
    }

    /**
     * Por cada usuario con turnos sin actividad, resuelve su primer
     * departamento y crea (o reusa) ahí una actividad "Sin especificar",
     * asignándosela a esos turnos. Un turno de un usuario sin ningún
     * departamento no tiene dónde crear esa actividad: se elimina (no
     * debería ocurrir en la práctica).
     */
    private function backfillMissingActivityTypes(): void
    {
        $userDepartments = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT ON (s.user_id) s.user_id, ud.department_id
             FROM shifts s
             JOIN user_departments ud ON ud.user_id = s.user_id
             WHERE s.activity_type_id IS NULL
             ORDER BY s.user_id, ud.department_id'
        );

        $placeholderByDepartment = [];

        foreach ($userDepartments as $row) {
            $departmentId = $row['department_id'];

            if (!isset($placeholderByDepartment[$departmentId])) {
                $existingId = $this->connection->fetchOne(
                    'SELECT id FROM activity_types WHERE department_id = ? AND name = ?',
                    [$departmentId, 'Sin especificar']
                );

                if (false !== $existingId) {
                    $placeholderByDepartment[$departmentId] = $existingId;
                } else {
                    $newId = Uuid::v7()->toRfc4122();
                    $this->connection->executeStatement(
                        'INSERT INTO activity_types (id, department_id, name, color) VALUES (?, ?, ?, NULL)',
                        [$newId, $departmentId, 'Sin especificar']
                    );
                    $placeholderByDepartment[$departmentId] = $newId;
                }
            }

            $this->connection->executeStatement(
                'UPDATE shifts SET activity_type_id = ? WHERE user_id = ? AND activity_type_id IS NULL',
                [$placeholderByDepartment[$departmentId], $row['user_id']]
            );
        }

        // Turnos que sigan sin actividad (usuario sin departamento alguno):
        // no hay dónde crear la actividad de relleno.
        $this->connection->executeStatement('DELETE FROM shifts WHERE activity_type_id IS NULL');
    }
}
