<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FriseurFabrik: table for appointment requests sent via the website forms';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on MySQL/MariaDB.');

        $this->addSql("CREATE TABLE friseurfabrik_base_domain_model_appointmentrequest (persistence_object_identifier VARCHAR(40) NOT NULL, createdat DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', sitenodename VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, service VARCHAR(255) DEFAULT NULL, stylist VARCHAR(255) DEFAULT NULL, preferreddate DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', preferredtime VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_4094D676EF814631134E4D9E (sitenodename, createdat), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on MySQL/MariaDB.');

        $this->addSql('DROP TABLE friseurfabrik_base_domain_model_appointmentrequest');
    }
}
