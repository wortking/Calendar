<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A partir de ahora un departamento siempre pertenece a una empresa (deja de
 * ser opcional). El único departamento existente sin empresa ("General") se
 * asigna a la única empresa que existe hoy (Polideportivo Espartales) antes
 * de aplicar la restricción, para no dejar filas inconsistentes.
 */
final class Version20260920000000 extends AbstractMigration
{
    private const FALLBACK_COMPANY_ID = '01a0bab5-d145-73f7-9f69-2b3bf248273d';

    public function getDescription(): string
    {
        return 'departments.company_id pasa a ser obligatorio (NOT NULL).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE departments SET company_id = '".self::FALLBACK_COMPANY_ID."' WHERE company_id IS NULL");
        $this->addSql('ALTER TABLE departments ALTER COLUMN company_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE departments ALTER COLUMN company_id DROP NOT NULL');
    }
}
