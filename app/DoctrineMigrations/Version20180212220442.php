<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180212220442 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema)
    {
        $this->addSql('
                UPDATE timetable_row_times trt
                LEFT JOIN timetable_row tr ON tr.old_timetable_row_id = trt.timetable_row_id AND tr.timetable_id = trt.timetable_id
                SET timetable_row_id = tr.id
            ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
