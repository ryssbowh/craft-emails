<?php

namespace Ryssbowh\CraftEmails\Services;

use Ryssbowh\CraftEmails\Emails;

class EmailsVariable
{
    public function emails()
    {
        return Emails::$plugin->emails;
    }

    public function emailShots()
    {
        return Emails::$plugin->emailShots;
    }

    public function mailchimp()
    {
        return Emails::$plugin->mailchimp;
    }

    public function emailSources()
    {
        return Emails::$plugin->emailSources;
    }

    public function attachements()
    {
        return Emails::$plugin->attachements;
    }
}