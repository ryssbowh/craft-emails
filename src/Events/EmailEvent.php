<?php 

namespace Ryssbowh\CraftEmails\Events;

use yii\base\Event;

class EmailEvent extends Event
{
    public $email;

    public $isNew;
}