<?php

namespace Ryssbowh\CraftEmails\emailSources;

use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use craft\base\Component;
use craft\elements\User;
use craft\models\UserGroup;

class UserGroupEmailSource extends Component implements EmailSourceInterface
{   
    /**
     * @var UserGroup
     */
    public $group;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return \Craft::t('emails', 'All users in group {group}', ['group' => $this->group->name]);
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return 'user_group_' . $this->group->handle;
    }

    /**
     * @inheritDoc
     */
    public function getEmails(): array
    {
        return array_map(function ($user) {
            return $user->email;
        }, User::find()->group($this->group)->all());
    }
}