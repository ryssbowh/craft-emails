<?php 

namespace Ryssbowh\CraftEmails\records;

use Ryssbowh\CraftEmails\models\Email as EmailModel;
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