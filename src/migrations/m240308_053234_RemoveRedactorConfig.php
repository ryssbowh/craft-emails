<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240308_053234_RemoveRedactorConfig migration.
 */
class m240308_053234_RemoveRedactorConfig extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropColumn('{{%emails}}', 'redactorConfig');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->addColumn('{{%emails}}', 'redactorConfig', $this->string(255)->after('system'));
        return true;
    }
}
