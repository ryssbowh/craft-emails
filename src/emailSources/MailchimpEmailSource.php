<?php

namespace Ryssbowh\CraftEmails\emailSources;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\MailchimpList;
use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use craft\base\Component;
use craft\elements\User;
use craft\models\UserGroup;

class MailchimpEmailSource extends Component implements EmailSourceInterface
{   
    /**
     * @var string
     */
    public $id;

    /**
     * Get mailchimp list
     * 
     * @return array
     */
    public function getList(): MailchimpList
    {
        return Emails::$plugin->mailchimp->getList($this->id);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return \Craft::t('emails', 'Mailchimp list {list}', ['list' => $this->list->name]);
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return 'mailchimp_list_' . $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getEmails(): array
    {
        return $this->list->emails;
    }
}