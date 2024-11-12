<?php

namespace brasstacksweb\craftrelevance\records;

use craft\db\ActiveRecord;
use craft\records\Element;

/**
 * @param {int} elementId
 * @param {blob} embedding
 * @param {blob} contentHash
 */
class Embedding extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%relevance_embeddings}}';
    }

    public function getElement()
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }
}
