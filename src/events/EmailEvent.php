<?php 

namespace Ryssbowh\CraftEmails\events;

use yii\base\Event;

class EmailEvent extends Event
{
    public $email;

    public $isNew;
}