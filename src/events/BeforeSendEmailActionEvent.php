<?php 

namespace Ryssbowh\CraftEmails\events;

use yii\base\Event;

class BeforeSendEmailActionEvent extends Event
{
    /**
     * @var array
     */
    public $variables;
}