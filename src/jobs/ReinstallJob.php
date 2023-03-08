<?php

namespace Ryssbowh\CraftEmails\jobs;

use Ryssbowh\CraftEmails\Emails;
use craft\queue\BaseJob;

/**
 * @since 2.0.8
 */
class ReinstallJob extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue): void
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