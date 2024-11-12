<?php

namespace brasstacksweb\craftrelevance\records;

use craft\elements\db\ElementQuery;
use brasstacksweb\craftrelevance\services\Relevance;

class RelevanceVariable
{
    public function to(int $id): ElementQuery
    {
        return Relevance::to($id);
    }
}
