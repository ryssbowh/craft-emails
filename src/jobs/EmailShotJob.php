<?php

namespace Ryssbowh\CraftEmails\jobs;

use Ryssbowh\CraftEmails\Emails;
use craft\queue\BaseJob;

class EmailShotJob extends BaseJob
{
    public $shot;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        Emails::$plugin->emailShots->sendNow($this->shot);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('emails', 'Sending ' . $this->shot->description);
    }

}