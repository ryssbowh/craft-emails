<?php 

namespace Ryssbowh\CraftEmails\records;

use Ryssbowh\CraftEmails\models\EmailLog as LogModel;
use craft\db\ActiveRecord;
use craft\helpers\FileHelper;

class EmailLog extends ActiveRecord
{
    /**
     * @var string
     */
    public $body;

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return '{{%emails_logs}}';
    }

    /**
     * transform to model
     * 
     * @return LogModel
     */
    public function toModel()
    {
        return new LogModel($this->getAttributes());
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        $file = \Craft::getAlias('@storage/logs/emails/' . $this->uid);
        FileHelper::createDirectory(dirname($file));
        file_put_contents($file, $this->body);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        $file = \Craft::getAlias('@storage/logs/emails/' . $this->uid);
        if (file_exists($file)) {
            unlink($file);
        }
        parent::afterDelete();
    }
}