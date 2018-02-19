<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180218164523 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO itkdev_setting (name, section, description, type, form_type, value_string) VALUES ('app_about_header', 'cms', 'The about header', 'string', 'text', 'About this app')");
        $this->addSql("INSERT INTO itkdev_setting (name, section, description, type, form_type, value_string) VALUES ('app_about_lead', 'cms', 'The about lead', 'string', 'text', null)");
        $this->addSql("INSERT INTO itkdev_setting (name, section, description, type, form_type, value_text) VALUES ('app_about_content', 'cms', 'The about content', 'text', 'ckeditor', '<h2>This app lets you …</h2>')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_about_content'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_about_lead'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_about_header'");
    }
}
