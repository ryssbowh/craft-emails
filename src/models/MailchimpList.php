<?php

namespace Ryssbowh\CraftEmails\models;

use yii\base\DynamicModel;

class MailchimpList extends DynamicModel
{
    /**
     * @var MailchimpMember[]
     */
    public $members = [];

    /**
     * Get all subscribed members emails of this list
     * 
     * @return string[]
     */
    public function getEmails(): array
    {
        $emails = [];
        foreach ($this->members as $member) {
            if ($member->status == 'subscribed') {
                $emails[$member->email_address] = $member->full_name;
            }
        }
        return $emails;
    }
}