<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220406_030612_ChangeInstructionsField migration.
 */
class m220406_030612_ChangeInstructionsField extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn('{{%emails}}', 'instructions', $this->string(500)->defaultValue(''));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220406_030612_ChangeInstructionsField cannot be reverted.\n";
        return false;
    }
}
