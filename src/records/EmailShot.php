<?php 

namespace Ryssbowh\CraftEmails\records;

use Ryssbowh\CraftEmails\models\EmailShot as EmailShotModel;
use craft\db\ActiveRecord;

class EmailShot extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails_shots}}';
    }

    public function toModel()
    {
        return new EmailShotModel($this->getAttributes());
    }
}