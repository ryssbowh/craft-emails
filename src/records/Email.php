<?php 

namespace Ryssbowh\CraftEmails\records;

use Ryssbowh\CraftEmails\models\Email as EmailModel;
use craft\db\ActiveRecord;

class Email extends ActiveRecord
{
    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return '{{%emails}}';
    }

    /**
     * Turn record to model
     * 
     * @return EmailModel
     */
    public function toModel()
    {
        return new EmailModel($this->getAttributes());
    }
}