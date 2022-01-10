<?php 

namespace Ryssbowh\CraftEmails\events;

use yii\base\Event;

class EmailShotEvent extends Event
{
    public $shot;

    public $isNew;
}