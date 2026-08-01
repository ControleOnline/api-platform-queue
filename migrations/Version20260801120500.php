<?php

declare(strict_types=1);

namespace DoctrineMigrations\Queue;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260801120500 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add queue identity and compact display presentation settings.';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('queue')) {
            if (!$this->columnExists('queue', 'short_label')) {
                $this->addSql('ALTER TABLE `queue` ADD `short_label` VARCHAR(50) DEFAULT NULL');
            }
            if (!$this->columnExists('queue', 'icon')) {
                $this->addSql('ALTER TABLE `queue` ADD `icon` VARCHAR(80) DEFAULT NULL');
            }

            $this->addSql("UPDATE `queue` SET `short_label` = 'Fritadeira' WHERE `queue` = 'Gyros Fritadeira' AND (`short_label` IS NULL OR TRIM(`short_label`) = '')");
            $this->addSql("UPDATE `queue` SET `short_label` = 'Churrasco' WHERE `queue` = 'Gyros Churrasco' AND (`short_label` IS NULL OR TRIM(`short_label`) = '')");
        }

        if ($this->tableExists('display')) {
            if (!$this->columnExists('display', 'queue_identification_mode')) {
                $this->addSql("ALTER TABLE `display` ADD `queue_identification_mode` VARCHAR(16) NOT NULL DEFAULT 'short_label'");
            }
            if (!$this->columnExists('display', 'status_indicator_mode')) {
                $this->addSql("ALTER TABLE `display` ADD `status_indicator_mode` VARCHAR(16) NOT NULL DEFAULT 'bullet'");
            }
            if (!$this->columnExists('display', 'show_unit_quantity')) {
                $this->addSql('ALTER TABLE `display` ADD `show_unit_quantity` TINYINT(1) NOT NULL DEFAULT 0');
            }
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function tableExists(string $table): bool
    {
        return (bool) $this->connection->fetchOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool) $this->connection->fetchOne('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
    }
}
