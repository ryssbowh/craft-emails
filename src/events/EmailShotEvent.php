<?php 

namespace Ryssbowh\CraftEmails\events;

use Ryssbowh\CraftEmails\models\EmailShot;
use Ryssbowh\CraftEmails\records\EmailShot as EmailShotRecord;
use yii\base\Event;

class EmailShotEvent extends Event
{
    /**
     * @var EmailShot|EmailShotRecord
     */
    public $shot;

    /**
     * @var bool
     */
    public $isNew;
}