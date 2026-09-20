<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260919094449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_verification_tokens (user_id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_email_verification_tokens_user_id ON email_verification_tokens (user_id)');
        $this->addSql('CREATE INDEX idx_email_verification_tokens_token_hash ON email_verification_tokens (token_hash)');
        $this->addSql('CREATE TABLE password_reset_tokens (user_id UUID NOT NULL, code_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_password_reset_tokens_user_id ON password_reset_tokens (user_id)');
        $this->addSql('CREATE TABLE permissions (name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DEDCC6F5E237E06 ON permissions (name)');
        $this->addSql('CREATE TABLE refresh_tokens (user_id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1B3BC57DA ON refresh_tokens (token_hash)');
        $this->addSql('CREATE INDEX idx_refresh_tokens_user_id ON refresh_tokens (user_id)');
        $this->addSql('CREATE TABLE role_permissions (role_id UUID NOT NULL, permission_id UUID NOT NULL, PRIMARY KEY (role_id, permission_id))');
        $this->addSql('CREATE INDEX idx_role_permissions_role_id ON role_permissions (role_id)');
        $this->addSql('CREATE TABLE roles (name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B63E2EC75E237E06 ON roles (name)');
        $this->addSql('CREATE TABLE user_images (user_id UUID NOT NULL, url VARCHAR(500) NOT NULL, is_active BOOLEAN NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_user_images_user_id ON user_images (user_id)');
        $this->addSql('CREATE TABLE user_roles (user_id UUID NOT NULL, role_id UUID NOT NULL, PRIMARY KEY (user_id, role_id))');
        $this->addSql('CREATE INDEX idx_user_roles_user_id ON user_roles (user_id)');
        $this->addSql('CREATE TABLE users (email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, dni VARCHAR(20) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, sex VARCHAR(20) DEFAULT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE email_verification_tokens');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE user_images');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE users');
    }
}
