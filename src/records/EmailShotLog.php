<?php 

namespace Ryssbowh\CraftEmails\records;

use Ryssbowh\CraftEmails\models\EmailShotLog as EmailShotLogModel;
use craft\db\ActiveRecord;

class EmailShotLog extends ActiveRecord
{
    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return '{{%emails_shots_logs}}';
    }

    /**
     * Turn record to model
     * 
     * @return EmailShotLogModel
     */
    public function toModel()
    {
        return new EmailShotLogModel($this->getAttributes());
    }
}