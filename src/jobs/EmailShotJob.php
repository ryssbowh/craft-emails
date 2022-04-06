<?php

namespace Ryssbowh\CraftEmails\jobs;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\models\EmailShot;
use craft\elements\User;
use craft\queue\BaseJob;

class EmailShotJob extends BaseJob
{
    public EmailShot $shot;

    public ?int $userId;

    public bool $isConsole;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $user = $this->userId ? User::find()->id($this->userId)->one() : null;
        Emails::$plugin->emailShots->sendNow($this->shot, $user, $this->isConsole);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('emails', 'Sending {shot}', ['shot' => $this->shot->description]);
    }

}