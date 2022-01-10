<?php 

namespace Ryssbowh\CraftEmails\events;

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

    /**
     * Result of shot after being sent, contains succeeded and failed email addresses
     * 
     * @var array
     */
    public $result;
}