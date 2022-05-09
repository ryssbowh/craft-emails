<?php 

namespace Ryssbowh\CraftEmails\records;

use craft\db\ActiveRecord;
use craft\records\SystemMessage;

class EmailAttachement extends ActiveRecord
{
    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return '{{%emails_attachements}}';
    }

    /**
     * Get system message foreign record
     */
    public function getSystemMessage()
    {
        return $this->hasOne(SystemMessage::class, ['id' => 'message_id']);
    }
}