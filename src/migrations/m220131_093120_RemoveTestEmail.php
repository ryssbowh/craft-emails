<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft;
use Ryssbowh\CraftEmails\Emails;
use craft\db\Migration;

/**
 * m220131_093120_RemoveTestEmail migration.
 */
class m220131_093120_RemoveTestEmail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!\Craft::$app->projectConfig->readOnly) {
            $email = Emails::$plugin->emails->getByKey('test_email');
            if ($email) {
                Emails::$plugin->emails->delete($email, true);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220131_093120_RemoveTestEmail cannot be reverted.\n";
        return false;
    }
}
