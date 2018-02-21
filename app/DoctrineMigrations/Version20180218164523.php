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

        // Terms.

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_string) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'app_terms_header',
            'type' => 'string',
            'form_type' => 'text',
            'description' => 'The app terms header',
            'value' => 'Accept terms',
        ]);

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_string) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'app_terms_lead',
            'type' => 'string',
            'form_type' => 'text',
            'description' => 'The app terms lead',
            'value' => null,
        ]);

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_text) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'app_terms_content',
            'type' => 'text',
            'form_type' => 'ckeditor',
            'description' => 'The terms and conditions for using the app',
            'value' => '
<h2>Terms and conditions</h2>
',
        ]);

        // About.

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_string) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'app_about_header',
            'type' => 'string',
            'form_type' => 'text',
            'description' => 'The about header',
            'value' => 'About the app',
        ]);

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_string) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'app_about_lead',
            'type' => 'string',
            'form_type' => 'text',
            'description' => 'The about lead',
            'value' => null,
        ]);

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_text) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'app_about_content',
            'type' => 'text',
            'form_type' => 'ckeditor',
            'description' => '
Content on the "About" page.

Placeholders:

app://guide_url: Url to the app guide
',
            'value' => '
<a href="app://guide_url">View the app guide</a>
',
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_terms_content'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_terms_lead'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_terms_header'");

        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_about_content'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_about_lead'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'app_about_header'");
    }
}
