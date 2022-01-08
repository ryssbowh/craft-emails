<?php

namespace Ryssbowh\CraftEmails\Services;

use Ryssbowh\CraftEmails\Events\EmailShotEvent;
use Ryssbowh\CraftEmails\Events\SendEmailShotEvent;
use Ryssbowh\CraftEmails\Models\EmailShot;
use Ryssbowh\CraftEmails\Records\EmailShot as EmailShotRecord;
use Ryssbowh\CraftEmails\Records\EmailShotLog;
use Ryssbowh\CraftEmails\exceptions\EmailShotException;
use Ryssbowh\CraftEmails\jobs\EmailShotJob;
use craft\base\Component;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use yii\base\Event;
use yii\data\Pagination;

class EmailShotsService extends Component
{
    const EVENT_BEFORE_SAVE = 'event_before_save';
    const EVENT_AFTER_SAVE = 'event_after_save';
    const EVENT_AFTER_DELETE = 'event_after_delete';
    const EVENT_BEFORE_DELETE = 'event_before_delete';
    const EVENT_BEFORE_SEND = 'event_before_send';
    const EVENT_AFTER_SEND = 'event_after_send';

    protected $_shots = null;

    /**
     * Get all email shots
     * 
     * @return array
     */
    public function all(): array
    {
        if ($this->_shots === null) {
            $this->_shots = array_map(function ($shot) {
                return $shot->toModel();
            }, EmailShotRecord::find()->all());
        }
        return $this->_shots;
    }

    /**
     * Get email shot by id
     * 
     * @param  int  $id
     * @return EmailShot
     */
    public function getById(int $id): EmailShot
    {
        foreach ($this->all() as $shot) {
            if ($shot->id == $id) {
                return $shot;
            }
        }
        throw EmailShotException::noId($id);
    }

    /**
     * Get email shot by handle
     * 
     * @param  string $handle
     * @return EmailShot
     */
    public function getByHandle(string $handle): EmailShot
    {
        foreach ($this->all() as $shot) {
            if ($shot->handle == $handle) {
                return $shot;
            }
        }
        throw EmailShotException::noHandle($handle);
    }

    /**
     * Save an email shot
     * 
     * @param  EmailShot    $shot
     * @param  bool|boolean $validate
     * @return bool
     */
    public function save(EmailShot $shot, bool $validate = true): bool
    {
        $shot->scenario = 'create';
        if ($validate and !$shot->validate()) {
            return false;
        }
        $isNew = !$shot->id;

        $this->triggerEvent(self::EVENT_BEFORE_SAVE, new EmailShotEvent([
            'shot' => $shot,
            'isNew' => $isNew
        ]));

        if ($isNew) {
            $record = new EmailShotRecord;
        } else {
            $record = $this->getRecordById($shot->id);
        }

        $record->email_id = $shot->email_id;
        $record->useQueue = $shot->useQueue;
        $record->name = $shot->name;
        $record->handle = $shot->handle;
        $record->emails = $shot->emails;
        $record->sources = $shot->sources;
        $record->users = $shot->users;
        $record->saveLogs = $shot->saveLogs;

        if ($record->save(false)) {
            $this->_shots = null;
            $this->triggerEvent(self::EVENT_AFTER_SAVE, new EmailShotEvent([
                'shot' => $shot,
                'isNew' => $isNew
            ]));
            return true;
        }
        return false;
    }

    /**
     * Delete an email shot
     * 
     * @param  EmailShot $shot
     * @return bool
     */
    public function delete(EmailShot $shot): bool
    {
        $record = $this->getRecordById($shot->id);
        $this->triggerEvent(self::EVENT_BEFORE_DELETE, new EmailShotEvent([
            'shot' => $shot
        ]));
        if ($record->delete()) {
            $this->_shots = null;
            $this->triggerEvent(self::EVENT_AFTER_DELETE, new EmailShotEvent([
                'shot' => $shot
            ]));
            return true;
        }
        return false;
    }

    /**
     * Send an email shot
     * 
     * @param EmailShot $shot
     * @param $forceQueue Override the email shot useQueue parameter
     * @return bool
     */
    public function send(EmailShot $shot, ?bool $forceQueue = null): bool
    {
        $useQueue = $forceQueue ?? $shot->useQueue;
        if ($useQueue) {
            Queue::push(new EmailShotJob([
                'shot' => $shot
            ]));
            return true;
        }
        return $this->sendNow($shot);
    }

    /**
     * Send email shot now
     *
     * @return bool
     */
    public function sendNow(EmailShot $shot): bool
    {
        $event = new SendEmailShotEvent([
            'shot' => $shot
        ]);
        $this->triggerEvent(self::EVENT_BEFORE_SEND, $event);
        if (!$event->send) {
            \Craft::info($shot->description . " has been cancelled by event", 'emails');
            return false;
        }
        $email = \Craft::$app->getMailer()
            ->composeFromKey($shot->emailObject->key, $shot->variables);
        $success = [];
        foreach ($shot->allEmails as $emailAddress) {
            \Craft::info('Sending ' . $shot->description . ' to ' . $emailAddress, 'emails');
            try {
                $email->setTo($emailAddress)->send();
                $success[] = $emailAddress;
            } catch (\Exception $e) {
                \Craft::$app->errorHandler->handleException($e);
            }
        }
        $this->afterSend($shot, $success);
        $this->triggerEvent(self::EVENT_AFTER_SEND, $event);
        return true;
    }

    /**
     * Get email shot record by id
     * 
     * @param  int $id
     * @return EmailShotRecord
     */
    public function getRecordById(int $id): EmailShotRecord
    {
        $shot = EmailShotRecord::find(['id' => $id])->one();
        if (!$shot) {
            throw EmailShotException::noIdRecord($id);
        }
        return $shot;
    }

    /**
     * Get logs for an email shot
     * 
     * @param  EmailShot $shot
     * @param  string $order
     * @param  string $orderSide
     * @return array
     */
    public function getLogs(EmailShot $shot, string $order = 'dateCreated', string $orderSide = 'desc'): array
    {
        $query = EmailShotLog::find()->where(['shot_id' => $shot->id])->orderBy([$order => $orderSide == 'asc' ? SORT_ASC : SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination([
            'totalCount' => $countQuery->count()
        ]);
        $models = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();
        return [$models, $pages];
    }

    /**
     * Delete logs for an email shot
     * 
     * @param  EmailShot  $shot
     * @param  array|null $ids
     */
    public function deleteLogs(EmailShot $shot, ?array $ids = null)
    {
        if (is_array($ids)) {
            $logs = EmailShotLog::find()->where(['in', 'id', $ids])->andWhere(['shot_id' => $shot->id])->all();
            foreach ($logs as $log) {
                $log->delete();
            }
        } else {
            \Craft::$app->getDb()->createCommand()
                ->delete(EmailShotLog::tableName(), ['shot_id' => $shot->id])
                ->execute();
        }
    }

    /**
     * After sending email shot
     * 
     * @param  EmailShot $shot
     * @param  array     $emails
     */
    protected function afterSend(EmailShot $shot, array $emails)
    {
        if ($shot->id) {
            $record = $this->getRecordById($shot->id);
            $record->sent++;
            $record->save(false);
        }
        if ($shot->saveLogs) {
            foreach ($emails as $email) {
                $log = new EmailShotLog([
                    'email' => $email,
                    'shot_id' => $shot->id ? $shot->id : null,
                    'message' => $shot->description . ' sent'
                ]);
                $log->save(false);
            }
        }
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
