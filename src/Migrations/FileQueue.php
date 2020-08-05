<?php
declare(strict_types = 1);

namespace Migrations;

use RB\Cli\Models\FileQueueModel;
use RB\DB\Migrate\{Schema, Table};
use RB\DB\Migration;

class FileQueue extends Migration
{
    public function up(Schema $schema, Table $table): void
    {
        $table->setTable(FileQueueModel::getTable())
            ->id()
            ->tinyInteger('direction')
            ->string('file_path', 150)
            ->enum('status', FileQueueModel::STATUSES)->default(FileQueueModel::STATUS_NEW)
            ->tinyInteger('attempts')->default(0)
            ->timestamps();

        $schema->create($table);
    }

    public function down(Schema $schema): void
    {
        $schema->drop(FileQueueModel::getTable());
    }
}