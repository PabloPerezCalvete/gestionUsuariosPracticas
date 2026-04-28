<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421142256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipo (id INT AUTO_INCREMENT NOT NULL, marca VARCHAR(255) NOT NULL, modelo VARCHAR(255) NOT NULL, numero_serie VARCHAR(255) NOT NULL, tipo VARCHAR(50) NOT NULL, estado VARCHAR(50) NOT NULL, propietario_id INT DEFAULT NULL, INDEX IDX_C49C530B53C8D32C (propietario_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE equipo ADD CONSTRAINT FK_C49C530B53C8D32C FOREIGN KEY (propietario_id) REFERENCES usuario1 (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipo DROP FOREIGN KEY FK_C49C530B53C8D32C');
        $this->addSql('DROP TABLE equipo');
    }
}
