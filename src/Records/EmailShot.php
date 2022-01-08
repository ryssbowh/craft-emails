<?php 

namespace Ryssbowh\CraftEmails\Records;

use Ryssbowh\CraftEmails\Models\EmailShot as EmailShotModel;
use craft\db\ActiveRecord;

class EmailShot extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emails_shots}}';
    }

    public function toModel()
    {
        $attributes = $this->getAttributes();
        $attributes['sources'] = json_decode($attributes['sources'], true);
        $attributes['users'] = json_decode($attributes['users'], true);
        $attributes['emails'] = json_decode($attributes['emails'], true);
        return new EmailShotModel($attributes);
    }
}