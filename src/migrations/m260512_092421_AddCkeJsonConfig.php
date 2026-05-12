<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft;
use craft\db\Migration;

/**
 * m260512_092421_AddCkeJsonConfig migration.
 */
class m260512_092421_AddCkeJsonConfig extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn('{{%emails}}', 'ckeConfigJson', $this->text()->after('ckeConfig'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%emails}}', 'ckeConfigJson');
        return true;
    }
}
