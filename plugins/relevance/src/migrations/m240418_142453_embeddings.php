<?php

namespace craft\contentmigrations;

use craft\db\Migration;
use craft\db\Table;
use modules\similarity\records\Embedding;

/**
 * m240418_142453_embeddings migration.
 */
class m240418_142453_embeddings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        $this->createTable(Embedding::tableName(), [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'embedding' => $this->binary()->notNull(),
            'contentHash' => $this->binary()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Embedding::tableName(), ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        echo "m240418_142453_embeddings cannot be reverted.\n";

        return false;
    }
}
