<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FriseurFabrik: appointment workflow (confirmed/proposed time, customer token, note, history)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on MySQL/MariaDB.');

        $this->addSql("ALTER TABLE friseurfabrik_base_domain_model_appointmentrequest ADD updatedat DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD appointmentstart DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD durationminutes INT DEFAULT NULL, ADD assignedstylist VARCHAR(255) DEFAULT NULL, ADD token VARCHAR(64) DEFAULT NULL, ADD internalnote LONGTEXT DEFAULT NULL, ADD source VARCHAR(255) NOT NULL DEFAULT 'website', ADD history JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        // existing rows get a token and an empty history before the columns become mandatory
        $this->addSql("UPDATE friseurfabrik_base_domain_model_appointmentrequest SET token = SHA2(CONCAT(persistence_object_identifier, RAND()), 256) WHERE token IS NULL");
        $this->addSql("UPDATE friseurfabrik_base_domain_model_appointmentrequest SET token = LEFT(token, 48), history = '[]' WHERE history IS NULL OR LENGTH(token) > 48");
        $this->addSql("ALTER TABLE friseurfabrik_base_domain_model_appointmentrequest MODIFY token VARCHAR(64) NOT NULL, MODIFY source VARCHAR(255) NOT NULL, MODIFY history JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4094D6765F37A13B ON friseurfabrik_base_domain_model_appointmentrequest (token)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on MySQL/MariaDB.');

        $this->addSql('DROP INDEX UNIQ_4094D6765F37A13B ON friseurfabrik_base_domain_model_appointmentrequest');
        $this->addSql('ALTER TABLE friseurfabrik_base_domain_model_appointmentrequest DROP updatedat, DROP appointmentstart, DROP durationminutes, DROP assignedstylist, DROP token, DROP internalnote, DROP source, DROP history');
    }
}
