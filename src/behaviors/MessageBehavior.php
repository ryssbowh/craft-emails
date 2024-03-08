<?php

namespace Ryssbowh\CraftEmails\behaviors;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\models\Email;
use craft\ckeditor\Field;
use craft\ckeditor\Plugin;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
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
     * Get ckeditor input
     *
     * @since 2.1.0
     * @param  string $ckeConfig
     * @return string
     */
    public function ckeditorInput(?string $ckeConfig): string
    {
        if (!\Craft::$app->plugins->isPluginEnabled('ckeditor')) {
            return '<p class="error">' . \Craft::t('emails', 'You must install ckeditor in the settings') . '</p>';
        }
        try {
            Plugin::getInstance()->ckeConfigs->getByUid($ckeConfig);
        } catch (\Exception $e) {
            return '<p class="error">' . \Craft::t('emails', 'The ckeditor config is not valid for this email') . '</p>';
        }
        $config = [
            'type' => Field::class,
            'name' => 'Body',
            'handle' => 'body',
            'ckeConfig' => $ckeConfig
        ];
        $field = \Craft::$app->fields->createField($config);
        return $field->getInputHtml($this->owner->body, null);
    }
}
