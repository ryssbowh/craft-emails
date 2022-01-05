<?php 

namespace Ryssbowh\CraftEmails\Records;

use Ryssbowh\CraftEmails\Models\Email as EmailModel;
use craft\db\ActiveRecord;

class Email extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails}}';
    }

    public function toModel()
    {
        return new EmailModel($this->getAttributes());
    }
}