<?php 

namespace Ryssbowh\CraftEmails\Records;

use Ryssbowh\CraftEmails\Models\EmailShotLog as EmailShotLogModel;
use craft\db\ActiveRecord;

class EmailShotLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails_shots_logs}}';
    }

    public function toModel()
    {
        return new EmailShotLogModel($this->getAttributes());
    }
}