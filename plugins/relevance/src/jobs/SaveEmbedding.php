<?php

namespace brasstacksweb\craftrelevance\jobs;

use Craft;
use craft\queue\BaseJob;
use brasstacksweb\craftrelevance\services\Similarity;

class SaveEmbeddings extends BaseJob
{
    public array $elementIds;

    public function execute($queue): void
    {
        $total = count($this->elementIds);

        foreach ($this->elementIds as $i => $elementId) {
            $this->setProgress(
                $queue,
                $i / $total,
                Craft::t('app', 'Saving Embedding {step} of {total}', [
                    'step' => $i + 1,
                    'total' => $total,
                ])
            );

            if (!Similarity::saveEmbedding($elementId)) {
                Craft::warning("Unable to save embedding for element ID: {$elementId}", __METHOD__);
            }
        }
    }

    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Saving Embeddings...');
    }
}
