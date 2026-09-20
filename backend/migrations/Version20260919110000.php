<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Departamentos: tablas para "departments" y "user_departments" (un usuario
 * puede pertenecer a varios departamentos), más el permiso "departments.manage"
 * que ya exigen CreateDepartmentController/UpdateDepartmentController vía
 * #[IsGranted].
 */
final class Version20260919110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Departamentos: tablas departments/user_departments + permiso departments.manage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE departments (name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5D91FCE25E237E06 ON departments (name)');
        $this->addSql('CREATE TABLE user_departments (user_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY (user_id, department_id))');
        $this->addSql('CREATE INDEX idx_user_departments_user_id ON user_departments (user_id)');

        $this->addSql("INSERT INTO permissions (id, name, description) VALUES
            ('00000000-0000-7000-8000-000000000106', 'departments.manage', 'Gestionar departamentos')");

        $this->addSql("INSERT INTO role_permissions (role_id, permission_id) VALUES
            ('00000000-0000-7000-8000-000000000002', '00000000-0000-7000-8000-000000000106')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role_permissions WHERE permission_id = '00000000-0000-7000-8000-000000000106'");
        $this->addSql("DELETE FROM permissions WHERE id = '00000000-0000-7000-8000-000000000106'");
        $this->addSql('DROP TABLE user_departments');
        $this->addSql('DROP TABLE departments');
    }
}
