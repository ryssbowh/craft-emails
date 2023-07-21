<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230721_035705_AddCkEditorConfigField migration.
 */
class m230721_035705_AddCkEditorConfigField extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn('{{%emails}}', 'ckeConfig', $this->string(255)->after('redactorConfig')->defaultValue(''));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%emails}}', 'ckeConfig');
        return true;
    }
}
