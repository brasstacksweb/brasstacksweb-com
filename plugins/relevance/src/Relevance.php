<?php

namespace brasstacksweb\craftrelevance;

use Craft;
use brasstacksweb\craftrelevance\models\Settings;
use brasstacksweb\relevance\jobs\SaveEmbeddings;
use brasstacksweb\relevance\web\twig\variables\SimilarityVariable;
use craft\base\Element;
use craft\base\Model;
use craft\helpers\Queue;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

/**
 * Relevance plugin
 *
 * @method static Relevance getInstance()
 * @method Settings getSettings()
 * @author Brass Tacks Web <hello@brasstacksweb.com>
 * @copyright Brass Tacks Web
 * @license MIT
 */
class Relevance extends Plugin
{
    // TODO: Make enabled entires configurable
    public const ENTRY_TYPES = [
        4, // News
    ];

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('relevance/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            $event->sender->set('similarity', SimilarityVariable::class);
        });

        Event::on(Element::class, Element::EVENT_AFTER_SAVE, function (Event $event) {
            $element = $event->sender;
            if ($element->isCanonical && in_array($element->typeId, self::ENTRY_TYPES, true)) {
                Queue::push(new SaveEmbeddings([
                    'elementIds' => [$element->id],
                ]));
            }
        });

        // TODO: Events to handle
        // Entry section enabled for relevance
        // 1. Create rows in embeddings table for all entries in section
        // Element saved
        // 1. Create row in embeddings table
        // Element deleted
        // 1. Delete row in embeddings table
    }
}
