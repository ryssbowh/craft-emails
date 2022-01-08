<?php 

namespace Ryssbowh\CraftEmails\Records;

use craft\db\ActiveRecord;

class EmailShotLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails_shots_logs}}';
    }
}