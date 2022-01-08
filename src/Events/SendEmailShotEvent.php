<?php 

namespace Ryssbowh\CraftEmails\Events;

use Ryssbowh\CraftEmails\Models\EmailShot;
use yii\base\Event;

class SendEmailShotEvent extends Event
{   
    /**
     * @var EmailShot
     */
    public $shot;

    /**
     * @var boolean
     */
    public $send = true;
}