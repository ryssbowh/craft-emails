<?php 

namespace Ryssbowh\CraftEmails\Models;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Records\Email as EmailRecord;
use Ryssbowh\CraftEmails\helpers\EmailHelper;
use craft\base\Model;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use craft\models\SystemMessage;
use craft\records\SystemMessage as SystemMessageRecord;
use craft\redactor\Field as RedactorField;
use craft\services\EmailMessageRecord;
use craft\validators\TemplateValidator;

class Email extends Model
{
    public $id;   
    public $uid;   
    public $dateCreated;
    public $dateUpdated;
    public $template = 'emails/template';
    public $redactorConfig;
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

    protected $_template;
    protected $_attachements;

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return [
            [['id', 'uid', 'dateCreated', 'dateUpdated', 'attachements'], 'safe'],
            [['key', 'heading', 'template'], 'required'],
            [['key', 'heading', 'bcc', 'fromName', 'instructions'], 'string'],
            [['saveLogs', 'system', 'plain'], 'boolean'],
            ['template', 'string'],
            ['template', TemplateValidator::class],
            [['from', 'replyTo'], function ($attribute) {
                $email = \Craft::parseEnv($this->$attribute);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($attribute, $email . ' is not a valid email');
                    return false;
                }
            }],
            [['sent'], 'integer'],
            ['redactorConfig', 'in', 'range' => array_keys(Emails::$plugin->settings->redactorConfigOptions)],
            [['cc', 'bcc'], function ($attribute) {
                foreach (EmailHelper::parseEmails($this->$attribute) as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($attribute, $email . ' is not a valid email');
                        return false;
                    }
                }
            }],
            ['key', 'unique', 'targetClass' => EmailRecord::class, 'targetAttribute' => 'key', 'filter' => function ($query) {
                if ($this->id) {
                    $query->andWhere(['!=', 'id', $this->id]);
                }
            }],
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
     * Attachements setter
     * 
     * @param string|array $attachements
     */
    public function setAttachements($attachements)
    {
        if (is_string($attachements)) {
            $attachements = json_decode($attachements, true);
        }
        $this->_attachements = $attachements;
    }

    /**
     * Attachement getter
     * 
     * @return array
     */
    public function getAttachements()
    {
        return $this->_attachements;
    }

    /**
     * Populate email from post
     */
    public function populateFromPost()
    {
        $request = \Craft::$app->request;
        foreach ($this->safeAttributes() as $attribute) {
            if ($request->getParam($attribute) !== null) {
                $this->$attribute = $request->getParam($attribute);
            }
        }
    }

    /**
     * Get project config
     * 
     * @return array
     */
    public function getConfig(): array
    {
        $config = [
            'key' => $this->key,
            'system' => $this->system,
            'instructions' => $this->instructions,
            'redactorConfig' => $this->redactorConfig,
            'saveLogs' => $this->saveLogs,
            'plain' => $this->plain,
            'from' => $this->from,
            'replyTo' => $this->replyTo,
            'bcc' => $this->bcc,
            'cc' => $this->cc,
            'heading' => $this->heading,
            'instructions' => $this->instructions,
            'fromName' => $this->fromName,
            'template' => $this->template,
        ];
        return $config;
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

    /**
     * Get attachements as elements (assets)
     * 
     * @return array
     */
    public function getAttachementsElements(): array
    {
        if (!$this->attachements) {
            return [];
        }
        return Asset::find()->id($this->attachements)->all();
    }

    /**
     * Get redactor settings
     * 
     * @return array
     */
    public function getRedactorSettings(): array
    {
        $settings = [];
        if ($this->redactorConfig) {
            $file = \Craft::getAlias('@config/redactor/' . $this->redactorConfig);
            if (file_exists($file)) {
                $settings = json_decode(file_get_contents($file), true);
            }
        }
        if (isset($settings['plugins'])) {
            foreach ($settings['plugins'] as $plugin) {
                RedactorField::registerRedactorPlugin($plugin);
            }
        }
        return $settings;
    }

    public function renderSubject(): string
    {
    }

    public function renderBody(): string
    {
    }
}
