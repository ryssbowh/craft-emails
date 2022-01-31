<?php

namespace Ryssbowh\CraftEmails\services;

use Craft;
use Ryssbowh\CraftEmails\Emails;
use craft\base\Component;
use craft\models\SystemMessage;
use craft\records\SystemMessage as SystemMessageRecord;

class MessagesService extends Component
{
    /**
     * Replaces system messages with emails
     * 
     * @param  array  $messages
     * @return array
     */
    public function getAllSystemMessages(array $messages): array
    {
        $systemKeys = [];
        $langId = \Craft::$app->getSites()->getPrimarySite()->language;
        foreach ($messages as $index => $message) {
            $record = SystemMessageRecord::find()->where([
                'language' => $langId,
                'key' => $message['key']
            ])->one();
            $systemKeys[] = $message['key'];
            if ($record) {
                $messages[$index]['subject'] = $record->subject;
                $messages[$index]['body'] = $record->body;
            }
        }
        $otherMessages = SystemMessageRecord::find()
            ->where(['language' => $langId])
            ->andWhere(['not in', 'key', $systemKeys])
            ->all();
        foreach ($otherMessages as $message) {
            $email = Emails::$plugin->emails->getByKey($message->key);
            $messages[] = [
                'key' => $message->key,
                'heading' => $email->heading,
                'subject' => $message->subject,
                'body' => $message->body,
            ];
        }
        return $messages;
    }

    /**
     * Get the system message associated to an email key, for a language.
     * Defaults to primary site language
     * 
     * @param  string|null $langId
     * @return ?SystemMessage
     */
    public function getMessage(string $key, ?string $langId = null): ?SystemMessage
    {
        if ($langId === null) {
            $langId = \Craft::$app->getSites()->getPrimarySite()->language;
        }
        return \Craft::$app->systemMessages->getMessage($key, $langId);
    }

    /**
     * Change language of messages that are set on default languages.
     * If some messages were set on the new language they will be deleted
     * 
     * @param string $oldLanguage
     * @param string $newLanguage
     */
    public function updatePrimaryMessageLanguage(string $oldLanguage, string $newLanguage)
    {
        $messages = SystemMessageRecord::find()->where(['language' => $oldLanguage])->all();
        $ids = [];
        foreach ($messages as $message) {
            $message->language = $newLanguage;
            $message->save(false);
            $ids[] = $message->id;
        }
        $messages = SystemMessageRecord::find()
            ->where(['language' => $newLanguage])
            ->andWhere(['not in', 'id', $ids])
            ->all();
        foreach ($messages as $message) {
            $message->delete();
        }
    }

    /**
     * Save a system message
     * 
     * @param  SystemMessage $message
     * @param  string        $langId
     * @param  array         $attachements array of ids
     * @return bool
     */
    public function saveMessage(SystemMessage $message, string $langId, array $attachements = []): bool
    {
        if (\Craft::$app->systemMessages->saveMessage($message, $langId)) {
            Emails::$plugin->attachements->save($message->key, $langId, $attachements);
            return true;
        }
        return false;
    }

    /**
     * Add a translation
     * 
     * @param  string $key
     * @param  string $langId
     * @return bool
     */
    public function addTranslation(string $key, string $langId): bool
    {
        $default = $this->getMessage($key);
        $message = new SystemMessage([
            'key' => $key,
            'subject' => $default->subject,
            'body' => $default->body
        ]);
        return $this->saveMessage($message, $langId, Emails::$plugin->attachements->get($key));
    }

    /**
     * Delete a translation
     * 
     * @param  string $key
     * @param  string $langId
     * @return bool
     */
    public function deleteTranslation(string $key, string $langId): bool
    {
        if (\Craft::$app->getSites()->getPrimarySite()->language == $langId) {
            return false;
        }
        $record = SystemMessageRecord::findOne([
            'key' => $key,
            'language' => $langId,
        ]);
        if ($record) {
            return $record->delete();
        }
        return false;
    }
}
