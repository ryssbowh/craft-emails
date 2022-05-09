<?php 

namespace Ryssbowh\CraftEmails\events;

use Ryssbowh\CraftEmails\models\Email;
use Ryssbowh\CraftEmails\records\Email as EmailRecord;
use yii\base\Event;

class EmailEvent extends Event
{
    /**
     * @var Email|EmailRecord
     */
    public $email;

    /**
     * @var bool
     */
    public $isNew;
}