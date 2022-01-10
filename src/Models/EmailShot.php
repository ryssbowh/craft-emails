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
    protected $_users = [];

    /**
     * @var array
     */
    protected $_emails = [];

    /**
     * @var array
     */
    protected $_sources = [];

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
    public $saveLogs = true;

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
                if ($names = \Craft::$app->request->getBodyParam('names')) {
                    $emails = [];
                    foreach ($this->emails as $index => $email) {
                        if ($email) {
                            $emails[$email] = $names[$index];
                        }
                    }
                    $this->emails = $emails;
                }
                foreach ($this->emails as $email => $name) {
                    if ($email and !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->addError('emails', \Craft::t('yii', '{attribute} is not a valid email address.', ['attribute' => $email]));
                    }
                }
            }],
            ['users', function () {
                foreach ($this->users as $user) {
                    $elem = User::find()->id($user)->one();
                    if (!$elem) {
                        $this->addError('users', \Craft::t('emails', "User {user} doesn't exist", ['user' => $user]));
                    }
                }
            }],
            ['sources', function () {
                foreach ($this->sources as $source) {
                    if (!Emails::$plugin->emailSources->has($source)) {
                        $this->addError('sources', \Craft::t('emails', "Source {source} doesn't exist", ['source' => $source]));
                    }
                }
            }]
        ];
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'sources' => $this->sources,
            'users' => $this->users,
            'emails' => $this->emails,
            'email_id' => $this->email_id,
            'useQueue' => $this->useQueue,
            'saveLogs' => $this->saveLogs
        ];
    }
    /**
     * Users setter
     * 
     * @param string|array $users
     */
    public function setUsers($users)
    {
        if (is_string($users)) {
            $users = json_decode($users, true);
        }
        if (is_null($users)) {
            $users = [];
        }
        $this->_users = $users;
    }

    /**
     * Sources setter
     * 
     * @param string|array $sources
     */
    public function setSources($sources)
    {
        if (is_string($sources)) {
            $sources = json_decode($sources, true);
        }
        if (is_null($sources)) {
            $sources = [];
        }
        $this->_sources = $sources;
    }

    /**
     * Emails setter
     * 
     * @param string|array $emails
     */
    public function setEmails($emails)
    {
        if (is_string($emails)) {
            $emails = json_decode($emails, true);
        }
        if (is_null($emails)) {
            $emails = [];
        }
        $this->_emails = $emails;
    }

    /**
     * Emails getter
     * 
     * @return array
     */
    public function getEmails(): array
    {
        return $this->_emails;
    }

    /**
     * Users getter
     * 
     * @return array
     */
    public function getUsers(): array
    {
        return $this->_users;
    }

    /**
     * Sources getter
     * 
     * @return array
     */
    public function getSources(): array
    {
        return $this->_sources;
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
    public function getEmail(): ?Email
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
            $emails[$user->email] = $user->friendlyName;
        }
        foreach ($this->sourceObjects as $source) {
            $emails = array_merge($emails, $source->emails);
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
            return \Craft::t('emails', "email shot '{name}'", ['name' => $this->name]);
        }
        return \Craft::t('emails', "quick email shot");
    }
}