<?php 

namespace Ryssbowh\CraftEmails\Models;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\Email;
use Ryssbowh\CraftEmails\Records\Email as EmailRecord;
use Ryssbowh\CraftEmails\Records\EmailShot as EmailShotRecord;
use Ryssbowh\CraftEmails\exceptions\EmailSourceException;
use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use craft\base\Model;
use craft\elements\User;

class EmailShot extends Model
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $uid;

    /**
     * @var DateTime
     */
    public $dateCreated;

    /**
     * @var DateTime
     */
    public $dateUpdated;

    /**
     * @var integer
     */
    public $email_id;

    /**
     * @var string
     */
    public $handle;

    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $users;

    /**
     * @var array
     */
    public $emails;

    /**
     * @var array
     */
    public $sources;

    /**
     * @var boolean
     */
    public $useQueue;

    /**
     * @var integer
     */
    public $sent = 0;

    /**
     * @var boolean
     */
    public $saveLogs = false;

    /**
     * @var array
     */
    public $variables = [];

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return [
            [['id', 'uid', 'dateCreated', 'dateUpdated'], 'safe'],
            [['handle', 'name'], 'required', 'on' => 'create'],
            [['handle', 'name'], 'string', 'on' => 'create'],
            [['handle', 'name'], 'trim', 'on' => 'create'],
            [['useQueue', 'saveLogs'], 'boolean'],
            ['email_id', 'exist', 'targetClass' => EmailRecord::class, 'targetAttribute' => 'id'],
            ['handle', 'unique', 'targetClass' => EmailShotRecord::class, 'targetAttribute' => 'handle', 'filter' => function ($query) {
                if ($this->id) {
                    $query->andWhere(['!=', 'id', $this->id]);
                }
            }, 'on' => 'create'],
            ['emails', function () {
                foreach ($this->emails as $email) {
                    if ($email and !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->addError('emails', \Craft::t('emails', 'Email ' . $email . ' is not a valid email'));
                    }
                }
            }],
            ['users', function () {
                foreach ($this->users as $user) {
                    $elem = User::find()->id($user)->one();
                    if (!$elem) {
                        $this->addError('users', \Craft::t('emails', 'User ' . $user . " doesn't exist"));
                    }
                }
            }],
            ['sources', function () {
                foreach ($this->sources as $source) {
                    if (!Emails::$plugin->emailSources->has($source)) {
                        $this->addError('sources', \Craft::t('emails', 'Source ' . $source . " doesn't exist"));
                    }
                }
            }]
        ];
    }

    /**
     * @inheritDoc
     */
    public function __sleep()
    {
        return ['email_id', 'emails', 'users', 'sources', 'useQueue', 'variables', 'handle', 'name', 'id', 'uid'];
    }

    /**
     * Get user elements from their ids
     * 
     * @return array
     */
    public function getUserElements(): array
    {
        if (!$this->users) {
            return [];
        }
        return User::find()->id($this->users)->all();
    }

    /**
     * get email object
     * 
     * @return Email
     */
    public function getEmailObject(): ?Email
    {
        if ($this->email_id) {
            return Emails::$plugin->emails->getById($this->email_id);
        }
        return null;
    }

    /**
     * Get source objects
     * 
     * @return EmailSourceInterface[]
     */
    public function getSourceObjects(): array
    {
        $objects = [];
        foreach ($this->sources as $handle) {
            try {
                $objects[] = Emails::$plugin->emailSources->getByHandle($handle);
            } catch (EmailSourceException $e) {
            }
        }
        return $objects;
    }

    /**
     * Get all emails this shot will be sent to
     * 
     * @return array
     */
    public function getAllEmails(): array
    {
        $emails = $this->emails;
        foreach ($this->getUserElements() as $user) {
            $emails[] = $user->email;
        }
        foreach ($this->sourceObjects as $source) {
            $emails = array_merge($emails, $source->emails);
        }
        if (Emails::$plugin->settings->removeShotDuplicates) {
            return array_unique($emails);    
        }
        return $emails;
    }

    /**
     * Get the amount of emails this shot will be sent to
     * 
     * @return int
     */
    public function getEmailCount(): int
    {
        return sizeof($this->allEmails);
    }

    /**
     * Get email shot description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        if ($this->name) {
            return "email shot '" . $this->name . "'";
        }
        return "quick email shot";
    }
}