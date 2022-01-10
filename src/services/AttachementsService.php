<?php

namespace Ryssbowh\CraftEmails\services;

use Ryssbowh\CraftEmails\records\EmailAttachement;
use craft\base\Component;
use craft\elements\Asset;
use craft\records\SystemMessage;

class AttachementsService extends Component
{
    protected $_all;

    /**
     * Get attachements for a key and a language
     * 
     * @param  string      $key
     * @param  string|null $langId
     * @param  bool        $asAssets
     * @return array
     */
    public function get(string $key, string $langId = null, bool $asAssets = false): array
    {
        if ($langId === null) {
            $langId = \Craft::$app->getSites()->getPrimarySite()->language;
        }
        $ids = $this->all()[$key][$langId]['attachements'] ?? [];
        if (!$asAssets) {
            return $ids;
        }
        if ($ids) {
            return Asset::find()->id($ids)->all();
        }
        return [];
    }

    /**
     * Save attachements
     * 
     * @param  string $key
     * @param  string $langId
     * @param  array  $attachements array of ids
     */
    public function save(string $key, string $langId, array $attachements): bool
    {
        $record = $this->all()[$key][$langId]['record'] ?? null;
        $message = SystemMessage::find()->where([
            'key' => $key,
            'language' => $langId
        ])->one();
        if (!$record) {
            if (!$message) {
                return false;
            }
            $record = new EmailAttachement;
            $this->_all[$key][$langId]['record'] = $record;
        }
        $this->_all[$key][$langId]['attachements'] = $attachements;
        $record->attachements = $attachements;
        $record->message_id = $message->id;
        return $record->save();
    }

    /**
     * Delete attachements
     * 
     * @param  string      $key
     * @param  string|null $langId
     */
    public function delete(string $key, string $langId = null)
    {
        foreach ($this->all() as $key2 => $langs) {
            foreach ($langs as $langId2 => $attachements) {
                if ($key == $key2 and ($langId === null or $langId == $langId2)) {
                    $attachements['record']->delete();
                    unset($this->_all[$key2][$langId2]);
                }
            }
        }
    }

    /**
     * Load all attachements
     * 
     * @return array
     */
    protected function all(): array
    {
        if ($this->_all === null) {
            $this->_all = [];
            foreach (EmailAttachement::find()->with('systemMessage')->all() as $attachement) {
                $this->_all[$attachement->systemMessage->key][$attachement->systemMessage->language] = [
                    'attachements' => json_decode($attachement->attachements, true),
                    'record' => $attachement
                ];
            }
        }
        return $this->_all;
    }
}
