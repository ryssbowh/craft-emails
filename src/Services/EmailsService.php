<?php
/**
 * Emails plugin for Craft CMS 3.x
 *
 * @link      https://www.inspire.scot
 * @copyright Copyright (c) 2020 Boris Blondin
 */

namespace Ryssbowh\CraftEmails\Services;

use Craft;
use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Events\EmailEvent;
use Ryssbowh\CraftEmails\Models\Email;
use Ryssbowh\CraftEmails\Models\EmailLog;
use Ryssbowh\CraftEmails\Records\Email as EmailRecord;
use Ryssbowh\CraftEmails\Records\EmailLog as EmailLogRecord;
use Ryssbowh\CraftEmails\exceptions\EmailException;
use Ryssbowh\CraftEmails\helpers\EmailHelper;
use craft\base\Component;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\mail\Message;
use craft\web\View;
use yii\base\Event;
use yii\data\Pagination;
use yii\helpers\Markdown;

class EmailsService extends Component
{
    const CONFIG_KEY = 'emails';
    const EVENT_BEFORE_SAVE = 1;
    const EVENT_AFTER_SAVE = 2;
    const EVENT_BEFORE_APPLY_DELETE = 3;
    const EVENT_AFTER_DELETE = 4;
    const EVENT_BEFORE_DELETE = 5;

    protected $_emails = null;

    /**
     * Get all emails
     * 
     * @return array
     */
    public function all(): array
    {
        if ($this->_emails === null) {
            $this->_emails = array_map(function ($email) {
                return $email->toModel();
            }, EmailRecord::find()->all());
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
        foreach ($this->all() as $email) {
            if ($email->id == $id) {
                return $email;
            }
        }
        throw EmailException::noId($id);
    }

    /**
     * Get email by key
     * 
     * @param  string $key
     * @return ?Email
     */
    public function getByKey(string $key): ?Email
    {
        foreach ($this->all() as $email) {
            if ($email->key == $key) {
                return $email;
            }
        }
        return null;
    }

    /**
     * Install system emails.
     * Installation will be skipped if dataInstalled is true in project config
     * to avoid duplication when applying config
     */
    public function install(): bool
    {
        $dataInstalled = \Craft::$app->projectConfig->get('plugins.emails.dataInstalled', false) ?? false;
        if ($dataInstalled) {
            return false;
        }
        $messages = \Craft::$app->systemMessages->getAllMessages();
        foreach ($messages as $message) {
            $email = new Email([
                'key' => $message['key'],
                'heading' => $message['heading'],
                'subject' => $message['subject'],
                'body' => $message['body'],
                'system' => true
            ]);
            $this->save($email);
        }
        if (!\Craft::$app->projectConfig->readOnly) {
            \Craft::$app->projectConfig->set('plugins.emails.dataInstalled', true, null, false);
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
            foreach ($logs as $log) {
                $log->delete();
            }
        } else {
            \Craft::$app->getDb()->createCommand()
                ->delete(EmailLogRecord::tableName(), ['email_id' => $email->id])
                ->execute();
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
        $mailer = \Craft::$app->mailer;
        $message = \Craft::createObject([
            'class' => $mailer->messageClass,
            'mailer' => $mailer
        ]);
        $message->setSubject($log->subject);
        $message->setTextBody(strip_tags($log->uncompressedContent));
        $message->setFrom($log->from);
        $message->setReplyTo($log->replyTo);
        $message->setBcc($log->bcc);
        $message->setCc($log->cc);
        $message->setTo($log->to);
        foreach ($log->attachementsElements as $asset) {
            $fullPath = \Craft::getAlias($asset->volume->path) . '/' . $asset->path;
            $message->attach($fullPath, [
                'fileName' => $asset->title
            ]);
        }

        // Is there a custom HTML template set?
        if (Craft::$app->getEdition() === Craft::Pro && $mailer->template) {
            $template = $mailer->template;
            $templateMode = View::TEMPLATE_MODE_SITE;
        } else {
            // Default to the _special/email.html template
            $template = '_special/email';
            $templateMode = View::TEMPLATE_MODE_CP;
        }

        try {
            $html = \Craft::$app->view->renderTemplate($template, array_merge($message->variables ?? [], [
                'body' => Template::raw(Markdown::process($log->uncompressedContent)),
            ]), $templateMode);
            $message->setHtmlBody($html);
        } catch (\Throwable $e) {
            // Just log it and don't worry about the HTML body
            \Craft::warning('Error rendering email template: ' . $e->getMessage(), __METHOD__);
            \Craft::$app->getErrorHandler()->logException($e);
        }
        if ($mailer->send($message)) {
            $message->key = $log->email->key;
            $this->afterSent($message, $log->attachements);
            return true;
        }
        return false;
    }

    /**
     * Operations after an email is sent, increment sent counter, save logs.
     * 
     * @param  Message $message
     * @param  ?array $attachements override attachements
     */
    public function afterSent(Message $message, ?array $attachements = null)
    {
        if (!$message->key) {
            return;
        }
        $email = $this->getByKey($message->key);
        if (!$email) {
            return;
        }
        $record = $this->getRecordById($email->id);
        if (!$record->id) {
            return;
        }
        $record->sent = $record->sent + 1;
        $record->save(false);
        if ($record->saveLogs) {
            $children = $message->getSwiftMessage()->getChildren();
            $html = $text = null;
            foreach ($children as $child) {
                if (get_class($child) != 'Swift_MimePart') {
                    continue;
                }
                if ($child->getHeaders()->get('content-type')->getValue() == 'text/html') {
                    $html = $child->getBody();
                }
                if ($child->getHeaders()->get('content-type')->getValue() == 'text/plain') {
                    $text = $child->getBody();
                }
            }
            $content = $html ?? $text ?? '';
            if ($attachements === null) {
                $attachements = $email->attachements; 
            }
            $user = \Craft::$app->getUser()->getIdentity();
            $log = new EmailLogRecord([
                'email_id' => $record->id,
                'subject' => $message->getSubject(),
                'to' => (array) $message->getTo(),
                'bcc' => (array) $message->getBcc(),
                'cc' => (array) $message->getCc(),
                'content' => Emails::$plugin->settings->compressLogs ? gzdeflate($content) : $content,
                'from' => $message->getFrom(),
                'attachements' => $attachements,
                'replyTo' => $message->getReplyTo(),
                'user_id' => $user ? $user->id : null,
                'is_console' => \Craft::$app->request->isConsoleRequest
            ]);
            $log->save(false);
        }
    }

    /**
     * Replaces system messages with emails
     * 
     * @param  array  $messages
     * @return array
     */
    public function replaceSystemMessages(array $messages): array
    {
        $done = [];
        $out = [];
        foreach ($messages as $message) {
            if ($email = $this->getByKey($message['key'])) {
                $message['heading'] = $email->heading;
                $message['subject'] = $email->subject;
                $message['body'] = $email->body;
                $done[] = $email->key;
            }
            $out[] = $message;
        }
        foreach ($this->all() as $email) {
            if (in_array($email->key, $done)) {
                continue;
            }
            $out[] = [
                'key' => $email->key,
                'heading' => $email->heading,
                'subject' => $email->subject,
                'body' => $email->body,
            ];
        }
        return $out;
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
        $configDrivenable = array_keys(Emails::$plugin->settings->configDrivenOptions);
        $configDriven = Emails::$plugin->settings->configDriven;
        foreach ($configDrivenable as $param) {
            if (!in_array($param, $configDriven)) {
                $record->$param = $email->$param;
            }
        }
        $record->save(false);
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
            $email->redactorConfig = $data['redactorConfig'];
            $email->saveLogs = $data['saveLogs'];
            foreach (Emails::$plugin->settings->configDriven as $attribute) {
                $email->$attribute = $data[$attribute] ?? null;
            }
            $email->save(false);
            
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
        $parts = explode('.', self::CONFIG_KEY);
        foreach ($this->all() as $email) {
            $e->config[$parts[0]][$parts[1]][$email->uid] = $email->getConfig();
        }
    }

    /**
     * Modify message before it's sent
     * 
     * @param Message $message
     */
    public function modifyMessage(Message $message) 
    {
        if (!$message->key) {
            return;
        }
        $mail = $this->getByKey($message->key);
        if (!$mail) {
            return;
        }
        if ($mail->bcc) {
            $message->setBcc(EmailHelper::parseEmails($mail->bcc));
        }
        if ($mail->cc) {
            $message->setCc(EmailHelper::parseEmails($mail->cc));
        }
        if ($mail->from or $mail->fromName) {
            $settings = \Craft::$app->systemSettings->getSettings('email');
            $fromEmail = $mail->from ? \Craft::parseEnv($mail->from) : $settings['fromEmail'];
            $fromName = $mail->fromName ? \Craft::parseEnv($mail->fromName) : $settings['fromName'];
            $message->setFrom([$fromEmail => $fromName]);
        }
        if ($mail->replyTo) {
            $message->setReplyTo(\Craft::parseEnv($mail->replyTo));
        }
        if ($assets = $mail->attachementsElements) {
            foreach ($assets as $asset) {
                $fullPath = \Craft::getAlias($asset->volume->path) . '/' . $asset->path;
                $message->attach($fullPath, [
                    'fileName' => $asset->title
                ]);
            }
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
        return EmailRecord::findOne(['uid' => $uid]) ?? new EmailRecord;
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
}
