<?php 

namespace Ryssbowh\CraftEmails\records;

use craft\db\ActiveRecord;
use craft\records\SystemMessage;

class EmailAttachement extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails_attachements}}';
    }

    public function getSystemMessage()
    {
        return $this->hasOne(SystemMessage::class, ['id' => 'message_id']);
    }
}