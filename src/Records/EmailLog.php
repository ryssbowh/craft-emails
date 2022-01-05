<?php 

namespace Ryssbowh\CraftEmails\Records;

use Ryssbowh\CraftEmails\Models\EmailLog as LogModel;
use craft\db\ActiveRecord;

class EmailLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails_logs}}';
    }

    public function toModel()
    {
        return new LogModel($this->getAttributes());
    }
}