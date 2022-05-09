<?php

namespace Ryssbowh\CraftEmails\models\actions;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\events\BeforeSendEmailActionEvent;
use Ryssbowh\CraftEmails\exceptions\EmailSourceException;
use Ryssbowh\CraftEmails\jobs\SendEmailJob;
use Ryssbowh\CraftEmails\models\Email;
use Ryssbowh\CraftTriggers\interfaces\TriggerInterface;
use Ryssbowh\CraftTriggers\models\Action;
use craft\elements\User;
use craft\helpers\Queue;
use yii\base\Event;

class SendEmail extends Action
{
    const EVENT_BEFORE_SEND = 'event-before-send';

    /**
     * @var string
     */
    public $email;

    /**
     * @var array
     */
    public $emails = [];

    /**
     * @var array
     */
    public $users = [];

    /**
     * @var array
     */
    public $sources = [];

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return \Craft::t('emails', 'Send email');
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return 'send-email';
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['emails', 'users', 'sources'], 'safe'],
            ['email', 'required'],
            ['email', 'string'],
            ['emails', function () {
                $emails = [];
                foreach ($this->emails as $index => $email) {
                    if ($email) {
                        $emails[$email['email'] ?? ''] = $email['name'] ?? '';
                    }
                }
                $this->emails = $emails;
                foreach ($this->emails as $email => $name) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->addError('emails', \Craft::t('yii', '{attribute} is not a valid email address.', ['attribute' => $email]));
                    }
                }
            }],
            ['users', function () {
                if (!$this->users) {
                    $this->users = [];
                }
                foreach ($this->users as $user) {
                    $elem = User::find()->id($user)->one();
                    if (!$elem) {
                        $this->addError('users', \Craft::t('emails', "User {user} doesn't exist", ['user' => $user]));
                    }
                }
            }, 'skipOnEmpty' => false],
            ['sources', function () {
                if (!$this->sources) {
                    $this->sources = [];
                }
                foreach ($this->sources as $source) {
                    if (!Emails::$plugin->emailSources->has($source)) {
                        $this->addError('sources', \Craft::t('emails', "Source {source} doesn't exist", ['source' => $source]));
                    }
                }
            }, 'skipOnEmpty' => false]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        $email = $this->emailObject;
        if ($email) {
            return \Craft::t('emails', 'Send email {email} to {emailsCount} email(s), {usersCount} user(s) and {sourcesCount} source(s)', [
                'email' => $email->heading,
                'emailsCount' => sizeof($this->emails),
                'usersCount' => sizeof($this->users),
                'sourcesCount' => sizeof($this->sources)
            ]);
        }
        return \Craft::t('emails', 'Send email. Email has not been chosen');
    }

    /**
     * Get email object
     * 
     * @return ?Email
     */
    public function getEmailObject(): ?Email
    {
        if (!$this->email) {
            return null;
        }
        return Emails::$plugin->emails->getByUid($this->email);
    }

    /**
     * @inheritDoc
     */
    public function hasConfig(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function configTemplate(): ?string
    {
        return 'emails/actions/send-email';
    }

    /**
     * @inheritDoc
     */
    public function apply(TriggerInterface $trigger, array $data)
    {
        $emailObject = $this->emailObject;
        if (!$emailObject) {
            \Craft::info("Not sending email as email not defined", __METHOD__);
        }
        $event = new BeforeSendEmailActionEvent([
            'variables' => $data
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND, $event);
        $email = \Craft::$app->getMailer()
            ->composeFromKey($emailObject->key, $event->variables);
        foreach ($this->allRecipients as $emailAddress => $name) {
            $email->setTo([$emailAddress => $name])->send();
        }
    }

    /**
     * Get all recipients
     * 
     * @return array
     */
    public function getAllRecipients(): array
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
     * Get all emails
     * 
     * @return array
     */
    public function getAllEmails(): array
    {
        $emails = ['' => \Craft::t('emails', 'Select email')];
        foreach (Emails::$plugin->emails->all as $email) {
            $emails[$email->uid] = $email->heading;
        }
        return $emails;
    }
}