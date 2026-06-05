<?php

namespace Ryssbowh\CraftEmails\models;

use craft\ckeditor\Field;
use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\records\Email as EmailRecord;
use Ryssbowh\CraftEmails\helpers\EmailHelper;
use craft\base\Model;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use craft\models\SystemMessage;
use craft\records\SystemMessage as SystemMessageRecord;
use craft\validators\TemplateValidator;

class Email extends Model
{
    public $id;
    public $uid;
    public $dateCreated;
    public $dateUpdated;
    public $template = 'emails/template';
    public $ckeConfig = '';
    public $ckeConfigJson = [];
    public $system = false;
    public $plain = false;
    public $bcc;
    public $cc;
    public $heading = '';
    public $instructions = '';
    public $key;
    public $saveLogs = true;
    public $sent;
    public $from;
    public $fromName;
    public $replyTo;

    public function init(): void
    {
        parent::init();
        $this->normalizeCkeConfigJson();
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return [
            [['id', 'uid', 'dateCreated', 'dateUpdated', 'attachements', 'ckeConfigJson', 'ckeConfig'], 'safe'],
            [['key', 'heading', 'template'], 'required'],
            [['key', 'heading', 'bcc', 'fromName', 'instructions'], 'string'],
            [['saveLogs', 'system', 'plain'], 'boolean', 'trueValue' => true, 'falseValue' => false, 'skipOnEmpty' => false],
            ['template', 'string'],
            ['template', TemplateValidator::class],
            [
                ['from', 'replyTo'],
                function ($attribute) {
                    $email = \Craft::parseEnv($this->$attribute);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($attribute, $email . ' is not a valid email');
                        return false;
                    }
                },
            ],
            [['sent'], 'integer'],
            [
                ['cc', 'bcc'],
                function ($attribute) {
                    foreach (EmailHelper::parseEmails($this->$attribute) as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($attribute, $email . ' is not a valid email');
                            return false;
                        }
                    }
                },
            ],
            [
                'key',
                'unique',
                'targetClass' => EmailRecord::class,
                'targetAttribute' => 'key',
                'filter' => function ($query) {
                    if ($this->id) {
                        $query->andWhere(['!=', 'id', $this->id]);
                    }
                },
            ],
        ];
    }

    /**
     * Get cp edit url
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('emails/edit/' . $this->id);
    }

    /**
     * Populate email from post
     */
    public function populateFromPost()
    {
        $request = \Craft::$app->request;
        foreach ($this->safeAttributes() as $attribute) {
            if ($request->getParam($attribute) !== null) {
                $value = $request->getParam($attribute);
                if ($attribute === 'ckeConfigJson') {
                    $value['toolbar'] = json_decode($value['toolbar'] ?? '[]', true);
                    $value['entryTypes'] = array_map(function ($config) {
                        return json_decode($config, true);
                    }, $value['entryTypes'] ?? []);
                }
                $this->$attribute = $value;
            }
        }
        $this->normalizeCkeConfigJson();
    }

    /**
     * Returns the entry types from cke config, if any.
     *
     * @since 4.0.0
     */
    public function getEntryTypes(): array
    {
        return array_map(fn($config) => Field::entryType($config), $this->ckeConfigJson['entryTypes'] ?? []);
    }

    /**
     * Returns the image field uid from cke config, if any.
     *
     * @since 4.0.0
     */
    public function getImageFieldUid(): ?string
    {
        return $this->ckeConfigJson['imageFieldUid'] ?? null;
    }

    /**
     * Get project config
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'key' => $this->key,
            'system' => (bool) $this->system,
            'instructions' => $this->instructions,
            'saveLogs' => (bool) $this->saveLogs,
            'plain' => (bool) $this->plain,
            'from' => $this->from,
            'replyTo' => $this->replyTo,
            'bcc' => $this->bcc,
            'cc' => $this->cc,
            'heading' => $this->heading,
            'fromName' => $this->fromName,
            'template' => $this->template,
            'ckeConfig' => $this->ckeConfig,
            'ckeConfigJson' => $this->ckeConfigJson,
        ];
    }

    /**
     * Get the system message associated to that email, for a language.
     *
     * @param  string|null $language
     * @return ?SystemMessage
     */
    public function getMessage(?string $language = null): ?SystemMessage
    {
        return Emails::$plugin->messages->getMessage($this->key, $language);
    }

    /**
     * Get all languages for which a message is defined
     *
     * @return array
     */
    public function getAllDefinedLanguages()
    {
        $languages = [];
        foreach (\Craft::$app->i18n->getSiteLocales() as $locale) {
            $record = SystemMessageRecord::findOne([
                'key' => $this->key,
                'language' => $locale->id,
            ]);
            if ($record) {
                $languages[$locale->id] = $locale->displayName;
            }
        }
        asort($languages);
        return $languages;
    }

    protected function normalizeCkeConfigJson(): void
    {
        if (is_string($this->ckeConfigJson)) {
            $decoded = json_decode($this->ckeConfigJson, true);
            $this->ckeConfigJson = is_array($decoded) ? $decoded : [];
            return;
        }

        if (!is_array($this->ckeConfigJson)) {
            $this->ckeConfigJson = [];
        }
    }
}
