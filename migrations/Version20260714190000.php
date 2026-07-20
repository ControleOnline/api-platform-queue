<?php

declare(strict_types=1);

namespace DoctrineMigrations\Queue;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Baseline schema for queue module from s.controleonline.com";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('CREATE TABLE IF NOT EXISTS `display` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `display` varchar(50) CHARACTER SET utf8 NOT NULL,
  `display_type` enum(\'products\',\'orders\',\'tv\') CHARACTER SET utf8 NOT NULL DEFAULT \'products\',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `display_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `display_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `display_id` int(11) NOT NULL,
  `queue_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hardware_id` (`display_id`,`queue_id`),
  KEY `queue_id` (`queue_id`),
  CONSTRAINT `display_queue_ibfk_2` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `display_queue_ibfk_3` FOREIGN KEY (`display_id`) REFERENCES `display` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(50) CHARACTER SET utf8 NOT NULL,
  `status_in_id` int(11) NOT NULL,
  `status_working_id` int(11) NOT NULL,
  `status_out_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `queue` (`queue`,`company_id`) USING BTREE,
  KEY `company_id` (`company_id`),
  KEY `status_in_id` (`status_in_id`),
  KEY `status_out_id` (`status_out_id`),
  KEY `status_working_id` (`status_working_id`),
  CONSTRAINT `queue_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `queue_ibfk_2` FOREIGN KEY (`status_in_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `queue_ibfk_3` FOREIGN KEY (`status_out_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `queue_ibfk_4` FOREIGN KEY (`status_working_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
