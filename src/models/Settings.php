<?php

namespace Ryssbowh\CraftEmails\models;

use craft\base\Model;
use craft\helpers\FileHelper;

class Settings extends Model
{
    /**
     * @var string
     */
    public string $menuItemName = '';

    /**
     * @var boolean
     */
    public bool $compressLogs = true;

    /**
     * @var string
     */
    public string $mailchimpApiKey = '';

    /**
     * @var integer
     */
    public int $mailchimpCacheDuration = 60;

    /**
     * @var string
     */
    public $wysiwygEditor;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        if ($this->wysiwygEditor === null) {
            if (\Craft::$app->plugins->isPluginEnabled('redactor')) {
                $this->wysiwygEditor = 'redactor';
            } elseif (\Craft::$app->plugins->isPluginEnabled('ckeditor')) {
                $this->wysiwygEditor = 'ckeditor';
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return [
            [['menuItemName', 'mailchimpApiKey', 'wysiwygEditor'], 'string'],
            ['wysiwygEditor', 'required'],
            ['compressLogs', 'boolean'],
            ['mailchimpCacheDuration', 'integer']
        ];
    }

    /**
     * Get all defined redactor configuration files
     *
     * @since 2.1.0
     * @return array
     */
    public function getWysiwygOptions(): array
    {
        $options = ['' => 'Select'];
        if (\Craft::$app->plugins->isPluginEnabled('redactor')) {
            $options['redactor'] = 'Redactor';
        }
        if (\Craft::$app->plugins->isPluginEnabled('ckeditor')) {
            $options['ckeditor'] = 'CKEditor';
        }
        return $options;
    }

    /**
     * Is a wysiwyg editor valid
     *
     * @since 2.1.0
     * @param  string $editor
     * @return boolean
     */
    public function wysiwygEditorIsValid(?string $editor): bool
    {
        if ($editor == 'redactor') {
            return \Craft::$app->plugins->isPluginEnabled('redactor');
        }
        if ($editor == 'ckeditor') {
            return \Craft::$app->plugins->isPluginEnabled('ckeditor');
        }
        return false;
    }

    public function getRedactorConfigOptions(): array
    {
        $options = [];
        $path = \Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . 'redactor';
        if (is_dir($path)) {
            $files = FileHelper::findFiles($path, [
                'only' => ['*.json'],
                'recursive' => false
            ]);

            foreach ($files as $file) {
                $filename = basename($file);
                $options[$filename] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        ksort($options);
        return $options;
    }
}
