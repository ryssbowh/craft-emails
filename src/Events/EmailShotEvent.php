<?php 

namespace Ryssbowh\CraftEmails\Events;

use yii\base\Event;

class EmailShotEvent extends Event
{
    public $shot;

    public $isNew;
}