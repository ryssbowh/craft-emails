<?php 

namespace Ryssbowh\CraftEmails\Models;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Records\Email as EmailRecord;
use Ryssbowh\CraftEmails\helpers\EmailHelper;
use craft\base\Model;
use craft\elements\Asset;
use craft\redactor\Field as RedactorField;

class Email extends Model
{
    public $id;   
    public $uid;   
    public $dateCreated;
    public $dateUpdated;
    public $subject = '';
    public $redactorConfig;
    public $system = false;
    public $plain = false;
    public $body = '';
    public $bcc;
    public $cc;
    public $heading = '';
    public $instructions = '';
    public $key;
    public $saveLogs;
    public $sent;
    public $from;
    public $fromName;
    public $replyTo;

    protected $_attachements;

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return [
            [['id', 'uid', 'dateCreated', 'dateUpdated', 'attachements'], 'safe'],
            [['key', 'heading'], 'required'],
            [['subject', 'key', 'heading', 'body', 'bcc', 'fromName', 'instructions'], 'string'],
            [['saveLogs', 'system', 'plain'], 'boolean'],
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
            if ($request->getBodyParam($attribute) !== null) {
                $this->$attribute = $request->getBodyParam($attribute);
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
            'plain' => $this->plain
        ];
        foreach (Emails::$plugin->settings->configDriven as $attribute) {
            $config[$attribute] = $this->$attribute;
        }
        return $config;
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
}
