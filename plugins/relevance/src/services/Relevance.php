<?php

namespace brasstacksweb\craftrelevance\records;

use Craft;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\helpers\App;
use brasstacksweb\relevance\records\Embedding;

class Relevance
{
    public static function to(int $id): ElementQuery
    {
        // Get related elementIds from embeddings table based on cosine similarity
        $element = Craft::$app->elements->getElementById($id);
        $embedding = Embedding::findOne(['elementId' => $id]);
        $original = array_map(fn ($e) => floatval($e), explode(',', $embedding->embedding));
        $others = Embedding::find()
            ->from(['em' => Embedding::tableName()])
            ->joinWith(['element el'])
            ->where(['!=', 'em.elementId', $id])
            ->andWhere(['el.type' => $element::class])
            ->limit(null)
            ->all();

        $similar = array_map(function ($other) use ($original) {
            $compare = array_map(fn ($e) => floatval($e), explode(',', $other->embedding));
            $similarity = self::getRelevance($original, $compare);

            return [
                'elementId' => $other->elementId,
                'similarity' => $similarity,
            ];
        }, $others);

        usort($similar, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        $query = match ($element::class) {
            'craft\elements\Entry' => Entry::find()->section($element->section->handle),
            'craft\elements\Category' => Cateory::find()->group($element->group->handle),
            default => Element::find(),
        };

        return $query->id(array_map(fn ($e) => $e['elementId'], $similar))->fixedOrder();
    }

    public static function saveEmbedding(int $id): bool
    {
        $element = Craft::$app->elements->getElementById($id);

        if (!$element || $element->url === null) {
            return false;
        }

        $current = Embedding::findOne(['elementId' => $id]);
        $content = self::getContent($element->url);
        $contentHash = hash('sha256', $content);

        if ($current && $current->contentHash === $contentHash) {
            return true;
        }

        $embedding = self::getEmbedding($content);
        $record = new Embedding([
            'elementId' => $id,
            'embedding' => $embedding,
            'contentHash' => $contentHash,
        ]);

        return $record->save();
    }

    private static function getContent(string $url): string
    {
        // TODO: What is the best way to get entry content as text?
        // Maybe call file_get_contents on entry template path and strip tags?
        $endpoint = sprintf('https://content-parser.com/text?url=%s', $url);

        return file_get_contents($endpoint);
    }

    private static function getEmbedding(string $content): array
    {
        $endpoint = 'https://api.openai.com/v1/embeddings';
        $data = [
            'model' => 'text-embedding-3-small', // TODO: Make configurable
            'input' => $content,
        ];

        $res = Craft::createGuzzleClient()->request('POST', $endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.App::env('OPENAI_API_KEY'),
            ],
            'json' => $data,
        ]);

        // TODO: Handle errors
        if ($res->getStatusCode() !== 200) {
            return '';
        }

        return json_decode($res->getBody())->data[0]->embedding;
    }

    private static function getRelevance(array $a, array $b): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
