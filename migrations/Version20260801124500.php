<?php

declare(strict_types=1);

namespace DoctrineMigrations\Queue;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260801124500 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add the global group-name presentation gate to displays.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('display') || $this->columnExists('display', 'show_group_names')) {
            return;
        }

        $this->addSql('ALTER TABLE `display` ADD `show_group_names` TINYINT(1) NOT NULL DEFAULT 0');
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
