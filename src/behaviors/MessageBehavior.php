<?php

namespace Ryssbowh\CraftEmails\behaviors;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\models\Email;
use Ryssbowh\CraftEmails\helpers\RedactorHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\redactor\assets\field\FieldAsset;
use craft\redactor\assets\redactor\RedactorAsset;
use craft\validators\HandleValidator;
use yii\base\Behavior;
use yii\helpers\Json;

class MessageBehavior extends Behavior
{
    /**
     * Get email
     * 
     * @return ?Email
     */
    public function getEmail(): ?Email
    {
        return Emails::$plugin->emails->getByKey($this->owner->key);
    }

    /**
     * Get parsed body
     * 
     * @return string
     */
    public function getParsedBody()
    {
        return \Craft::$app->elements->parseRefs($this->owner->body);
    }

    /**
     * Get redactor input
     * 
     * @param  ?string $redactorConfig
     * @return string
     */
    public function redactorInput(?string $redactorConfig)
    {
        \Craft::$app->view->registerAssetBundle(FieldAsset::class);
        RedactorAsset::registerTranslations(\Craft::$app->view);
        $settings = RedactorHelper::getRedactorSettings($redactorConfig);
        \Craft::$app->view->registerJs('new Craft.RedactorInput(' . Json::encode($settings) . ');');
        $textarea = Html::textarea('body', $this->_parsedBody(), [
            'id' => 'field-body',
            'autocomplete' => 'off',
            'style' => ['display' => 'none'],
        ]);

        return Html::tag('div', $textarea, [
            'class' => [
                'redactor',
                'normal'
            ],
        ]);
    }

    public function _parsedBody()
    {
        if (!StringHelper::contains($this->owner->body, '{')) {
            return $this->owner->body;
        }
        return preg_replace_callback('/(href=|src=)([\'"])(\{([\w\\\\]+\:\d+(?:@\d+)?\:(?:transform\:)?' . HandleValidator::$handlePattern . ')(?:\|\|[^\}]+)?\})(?:\?([^\'"#]*))?(#[^\'"#]+)?\2/', function($matches) {
            /** @var Element|null $element */
            list ($fullMatch, $attr, $q, $refTag, $ref, $query, $fragment) = array_pad($matches, 7, null);
            $parsed = \Craft::$app->getElements()->parseRefs($refTag);
            // If the ref tag couldn't be parsed, leave it alone
            if ($parsed === $refTag) {
                return $fullMatch;
            }
            if ($query) {
                // Decode any HTML entities, e.g. &amp;
                $query = Html::decode($query);
                if (mb_strpos($parsed, $query) !== false) {
                    $parsed = UrlHelper::urlWithParams($parsed, $query);
                }
            }
            return $attr . $q . $parsed . ($fragment ?? '') . '#' . $ref . $q;
        }, $this->owner->body);
    }
}