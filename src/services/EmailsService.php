<?php

namespace Ryssbowh\CraftEmails\services;

use Craft;
use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\events\EmailEvent;
use Ryssbowh\CraftEmails\exceptions\EmailException;
use Ryssbowh\CraftEmails\helpers\EmailHelper;
use Ryssbowh\CraftEmails\models\Email;
use Ryssbowh\CraftEmails\models\EmailLog;
use Ryssbowh\CraftEmails\records\Email as EmailRecord;
use Ryssbowh\CraftEmails\records\EmailAttachement;
use Ryssbowh\CraftEmails\records\EmailLog as EmailLogRecord;
use craft\base\Component;
use craft\ckeditor\Plugin;
use craft\db\Table;
use craft\elements\Asset;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\mail\Message;
use craft\models\SystemMessage;
use craft\records\SystemMessage as SystemMessageRecord;
use craft\web\View;
use yii\base\Event;
use yii\data\Pagination;
use yii\helpers\Markdown;

class EmailsService extends Component
{
    public const CONFIG_KEY = 'emails';
    public const EVENT_BEFORE_SAVE = 'before-save';
    public const EVENT_AFTER_SAVE = 'after-save';
    public const EVENT_BEFORE_APPLY_DELETE = 'before-apply-delete';
    public const EVENT_AFTER_DELETE = 'after-delete';
    public const EVENT_BEFORE_DELETE = 'before-delete';

    protected $_emails = null;

    /**
     * Get all emails
     *
     * @return array
     */
    public function getAll(): array
    {
        if ($this->_emails === null) {
            $this->_emails = array_map(function ($email) {
                return $email->toModel();
            }, EmailRecord::find()->orderBy('heading asc')->all());
        }
        return $this->_emails;
    }

    /**
     * Get Email by id
     *
     * @param  int  $id
     * @return Email
     */
    public function getById(int $id): Email
    {
        foreach ($this->all as $email) {
            if ($email->id == $id) {
                return $email;
            }
        }
        throw EmailException::noId($id);
    }

    /**
     * Get Email by uid
     *
     * @param  string  $uid
     * @return Email
     */
    public function getByUid(string $uid): Email
    {
        foreach ($this->all as $email) {
            if ($email->uid == $uid) {
                return $email;
            }
        }
        throw EmailException::noUid($uid);
    }

    /**
     * Get all custom emails
     *
     * @return array
     * @since  2.0.8
     */
    public function getAllCustoms(): array
    {
        $emails = [];
        foreach ($this->all as $email) {
            if (!$email->system) {
                $emails[] = $email;
            }
        }
        return $emails;
    }

    /**
     * Get email by key
     *
     * @param  ?string $key
     * @return ?Email
     */
    public function getByKey(?string $key): ?Email
    {
        if (!$key) {
            return null;
        }
        foreach ($this->all as $email) {
            if ($email->key == $key) {
                return $email;
            }
        }
        return null;
    }

    /**
     * Install system emails.
     * Add missing emails and delete orphans if they don't exist both in project config and db
     */
    public function install(): bool
    {
        if (\Craft::$app->projectConfig->readOnly) {
            return true;
        }
        $messages = \Craft::$app->systemMessages->getAllMessages();
        $installed = [];
        $ckeConfig = null;
        if (\Craft::$app->plugins->isPluginEnabled('ckeditor')) {
            $ckeConfig = Plugin::getInstance()->ckeConfigs->getAll()[0] ?? null;
        }
        foreach ($messages as $message) {
            if ($message['key'] != 'test_email') {
                if (!$this->isInProjectConfig($message['key']) and !$this->getByKey($message['key'])) {
                    $email = new Email([
                        'key' => $message['key'],
                        'heading' => $message['heading'],
                        'system' => true,
                        'ckeConfig' => $ckeConfig->uid
                    ]);
                    $this->save($email);
                    $langId = \Craft::$app->getSites()->getPrimarySite()->language;
                    $message = new SystemMessage([
                        'key' => $email->key,
                        'subject' => $message->subject,
                        'body' => '<p>' . preg_replace("/[\n]+/", '</p><p>', $message->body) . '</p>',
                    ]);
                    Emails::$plugin->messages->saveMessage($message, $langId);
                }
                $installed[] = $message['key'];
            }
        }
        foreach ($this->getAll() as $email) {
            if (!in_array($email->key, $installed) and $this->isInProjectConfig($email->key)) {
                $this->delete($email, true);
            }
        }
        return true;
    }

    /**
     * Get logs for an email
     *
     * @param  Email  $email
     * @param  string $order
     * @param  string $orderSide
     * @return array
     */
    public function getLogs(Email $email, string $order = 'dateCreated', string $orderSide = 'desc'): array
    {
        $query = EmailLogRecord::find()->where(['email_id' => $email->id])->orderBy([$order => $orderSide == 'asc' ? SORT_ASC : SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination([
            'defaultPageSize' => 10,
            'totalCount' => $countQuery->count()
        ]);
        $models = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();
        $models = array_map(function ($record) {
            return $record->toModel();
        }, $models);
        return [$models, $pages];
    }

    /**
     * Get an email log by id
     *
     * @param  int    $id
     * @return EmailLog
     */
    public function getLogById(int $id): EmailLog
    {
        $log = EmailLogRecord::find()->where(['id' => $id])->one();
        if (!$log) {
            throw EmailException::noLogId($id);
        }
        return $log->toModel();
    }

    /**
     * Delete logs for an email
     *
     * @param  Email  $email
     * @param  array|null $ids
     */
    public function deleteLogs(Email $email, ?array $ids = null)
    {
        if (is_array($ids)) {
            $logs = EmailLogRecord::find()->where(['in', 'id', $ids])->andWhere(['email_id' => $email->id])->all();
        } else {
            $logs = EmailLogRecord::find()->where(['email_id' => $email->id])->all();
        }
        foreach ($logs as $log) {
            $log->delete();
        }
    }

    /**
     * Resend an email from a log
     *
     * @param  EmailLog $log
     * @return bool
     */
    public function resend(EmailLog $log): bool
    {
        return \Craft::$app->mailer->resend(
            $log->email->key,
            $log->subject,
            $log->textBody,
            $log->body,
            $log->from,
            $log->replyTo,
            $log->bcc,
            $log->cc,
            $log->to,
            $log->attachements
        );
    }

    /**
     * Save an email
     *
     * @param  Email        $email
     * @param  bool|boolean $validate
     * @return bool
     */
    public function save(Email $email, bool $validate = true): bool
    {
        if ($validate and !$email->validate()) {
            return false;
        }
        $isNew = !$email->id;
        $uid = $isNew ? StringHelper::UUID() : $email->uid;

        $this->triggerEvent(self::EVENT_BEFORE_SAVE, new EmailEvent([
            'email' => $email,
            'isNew' => $isNew
        ]));

        $projectConfig = \Craft::$app->getProjectConfig();
        $configData = $email->getConfig();
        $configPath = self::CONFIG_KEY . '.' . $uid;
        $projectConfig->set($configPath, $configData);

        $record = $this->getRecordByUid($uid);
        $email->setAttributes($record->getAttributes(), false);

        $this->_emails = null;

        return true;
    }

    /**
     * Delete an email
     *
     * @param  Email $email
     * @param  bool  $force
     * @return bool
     */
    public function delete(Email $email, bool $force = false): bool
    {
        if ($email->system and !$force) {
            throw EmailException::system($email->id);
        }
        $this->triggerEvent(self::EVENT_BEFORE_DELETE, new EmailEvent([
            'email' => $email
        ]));

        \Craft::$app->getProjectConfig()->remove(self::CONFIG_KEY . '.' . $email->uid);

        $this->_emails = null;

        return true;
    }

    /**
     * Handle project config change
     *
     * @param  ConfigEvent $event
     */
    public function handleChanged(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;
        $transaction = \Craft::$app->getDb()->beginTransaction();

        try {
            $email = $this->getRecordByUid($uid);
            $isNew = $email->getIsNewRecord();

            $email->uid = $uid;
            $email->key = $data['key'];
            $email->instructions = $data['instructions'];
            $email->system = $data['system'];
            $email->plain = $data['plain'];
            $email->saveLogs = $data['saveLogs'];
            $email->from = $data['from'];
            $email->replyTo = $data['replyTo'];
            $email->bcc = $data['bcc'];
            $email->cc = $data['cc'];
            $email->heading = $data['heading'];
            $email->ckeConfig = $data['ckeConfig'] ?? '';
            $email->fromName = $data['fromName'];
            $email->template = $data['template'];

            if (isset($email->getDirtyAttributes()['key'])) {
                \Craft::$app->getDb()->createCommand()
                    ->update(Table::SYSTEMMESSAGES, ['key' => $email->key], ['key' => $email->getOldAttribute('key')])
                    ->execute();
            }

            $email->save(false);

            if ($isNew) {
                $langId = \Craft::$app->getSites()->getPrimarySite()->language;
                $message = new SystemMessage([
                    'key' => $email->key,
                    'subject' => 'Subject here',
                    'body' => '<p>Body here</p>'
                ]);
                Emails::$plugin->messages->saveMessage($message, $langId);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->triggerEvent(self::EVENT_AFTER_SAVE, new EmailEvent([
            'email' => $email,
            'isNew' => $isNew,
        ]));
    }

    /**
     * Handle project config deletion
     *
     * @param  ConfigEvent $event
     */
    public function handleDeleted(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $email = $this->getRecordByUid($uid);

        if (!$email) {
            return;
        }

        $this->triggerEvent(self::EVENT_BEFORE_APPLY_DELETE, new EmailEvent([
            'email' => $email
        ]));

        \Craft::$app->getDb()->createCommand()
            ->delete(EmailRecord::tableName(), ['uid' => $uid])
            ->execute();
        Emails::$plugin->attachements->delete($email->key);
        \Craft::$app->getDb()->createCommand()
            ->delete(Table::SYSTEMMESSAGES, ['key' => $email->key])
            ->execute();

        $this->triggerEvent(self::EVENT_AFTER_DELETE, new EmailEvent([
            'email' => $email
        ]));
    }

    /**
     * Respond to rebuild config event
     *
     * @param RebuildConfigEvent $e
     */
    public function rebuildConfig(RebuildConfigEvent $e)
    {
        foreach ($this->all as $email) {
            $e->config[self::CONFIG_KEY][$email->uid] = $email->getConfig();
        }
    }

    /**
     * Get record by id
     *
     * @param  string $uid
     * @return ?EmailRecord
     */
    public function getRecordById(string $id): ?EmailRecord
    {
        return EmailRecord::findOne(['id' => $id]);
    }

    /**
     * Get record by uid
     *
     * @param  string $uid
     * @return EmailRecord
     */
    protected function getRecordByUid(string $uid): EmailRecord
    {
        return EmailRecord::findOne(['uid' => $uid]) ?? new EmailRecord();
    }

    /**
     * Trigger an event
     *
     * @param string $type
     * @param Event  $event
     */
    protected function triggerEvent(string $type, Event $event)
    {
        if ($this->hasEventHandlers($type)) {
            $this->trigger($type, $event);
        }
    }

    /**
     * Does an email key exist in project config files
     *
     * @param  string  $key
     * @return boolean
     * @since  2.0.8
     */
    protected function isInProjectConfig(string $key): bool
    {
        $keys = array_map(function ($data) {
            return $data['key'];
        }, \Craft::$app->projectConfig->get(self::CONFIG_KEY, true) ?? []);
        return in_array($key, $keys);
    }
}
