<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180212212550 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE timetable_row ADD timetable_id int null');
        $this->addSql('ALTER TABLE timetable_row ADD old_timetable_row_id int null');

        $rows = $this->connection->query('SELECT * FROM timetable_row')->fetchAll();
        $timetables = $this->connection->query('SELECT id FROM timetable')->fetchAll();

        foreach ($timetables as $timetable) {
            foreach ($rows as $row) {
                $this->addSql('
                    INSERT INTO timetable_row 
                    (manager_id, customer_id, provider_id, object, mechanism, comment, price_for_customer, price_for_provider, timetable_id, old_timetable_row_id)
                    VALUES (:manager_id, :customer_id, :provider_id, :object, :mechanism, :comment, :price_for_customer, :price_for_provider, :timetable_id, :old_timetable_row_id)
                    ',
                    [
                        'timetable_id' => $timetable['id'],
                        'old_timetable_row_id' => $row['id'],
                        'manager_id' => $row['manager_id'],
                        'customer_id' => $row['customer_id'],
                        'provider_id' => $row['provider_id'],
                        'object' => $row['object'],
                        'mechanism' => $row['mechanism'],
                        'comment' => $row['comment'],
                        'price_for_customer' => $row['price_for_customer'],
                        'price_for_provider' => $row['price_for_provider'],
                    ]
                );
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
