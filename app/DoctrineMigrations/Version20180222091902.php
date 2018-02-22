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
class Version20180222091902 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // App message templates.

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_text) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'guest_app_sms_body_template',
            'type' => 'text',
            'form_type' => 'textarea',
            'description' => 'Template for message sent to guest when app is ready.

Placeholders:

{{ guest.name }}
{{ app_url }}
{{ site_name }}
',
            'value' => 'Hej {{ guest.name }}

Du kan hente din personlige Dokk1-gæste-app på {{ app_url }}

Venlig hilsen
{{ site_name }}',
        ]);

        $this->addSql('INSERT INTO itkdev_setting (name, type, form_type, description, value_text) VALUES (:name, :type, :form_type, :description, :value)', [
            'name' => 'guest_code_sms_template',
            'type' => 'text',
            'form_type' => 'textarea',
            'description' => 'Template for message sent to guest with generated code.

Placeholders:

{{ guest.name }}
{{ code.template.name }}
{{ code_valid_time_period }}
{{ site_name }}
',
            'value' => 'Hej {{ guest.name }}

Her er din adgangskode til Dokk1: {{ code.identifier }}

Koden indtastes på tal-panelet ved siden af døren eller inde i elevatoren. OBS: Når du taster koden skal du huske at afslutte med E, dvs. du skal taste “{{ code.identifier }}E”.

Koden giver adgang til “{{ code.template.name }}” i tidsrummet {{ code_valid_time_period }}.

Venlig hilsen
{{ site_name }}',
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'guest_code_sms_template'");
        $this->addSql("DELETE FROM itkdev_setting WHERE name = 'guest_app_sms_body_template'");
    }
}
