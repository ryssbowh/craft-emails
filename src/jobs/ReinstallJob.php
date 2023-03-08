<?php

namespace Ryssbowh\CraftEmails\jobs;

use Ryssbowh\CraftEmails\Emails;
use craft\queue\BaseJob;

/**
 * @since 1.4.8
 */
class ReinstallJob extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        Emails::$plugin->emails->install();
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('emails', 'Reinstalling emails');
    }
}