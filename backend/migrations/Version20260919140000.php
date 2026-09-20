<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Empresas: horario de apertura/cierre que acota el calendario de sus
 * departamentos y valida los turnos de sus miembros. Un departamento puede
 * no tener empresa (company_id nullable) — en ese caso no hay restricción,
 * igual que antes de esta migración.
 */
final class Version20260919140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Empresas: tabla companies + departments.company_id + permisos companies.manage/companies.view.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE companies (id UUID NOT NULL, name VARCHAR(255) NOT NULL, opening_time TIME(0) WITHOUT TIME ZONE NOT NULL, closing_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3A5E237E06 ON companies (name)');

        $this->addSql('ALTER TABLE departments ADD company_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_departments_company_id ON departments (company_id)');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000109', 'companies.manage', 'Gestionar empresas'),
            ('00000000-0000-7000-8000-00000000010a', 'companies.view', 'Ver el listado de empresas')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000109'),
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-00000000010a')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id IN ('00000000-0000-7000-8000-000000000109', '00000000-0000-7000-8000-00000000010a')");
        $this->addSql("DELETE FROM permissions WHERE id IN ('00000000-0000-7000-8000-000000000109', '00000000-0000-7000-8000-00000000010a')");
        $this->addSql('DROP INDEX idx_departments_company_id');
        $this->addSql('ALTER TABLE departments DROP company_id');
        $this->addSql('DROP TABLE companies');
    }
}
