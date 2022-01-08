<?php

namespace Ryssbowh\CraftEmails\emailSources;

use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use craft\base\Component;
use craft\elements\User;

class AllUsersEmailSource extends Component implements EmailSourceInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return \Craft::t('emails', 'All users');
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return 'all_users';
    }

    /**
     * @inheritDoc
     */
    public function getEmails(): array
    {
        $emails = [];
        foreach (User::find()->all() as $user) {
            $emails[$user->email] = $user->friendlyName;
        }
        return $emails;
    }
}