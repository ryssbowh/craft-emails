<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220109_090357_Version11Changes migration.
 */
class m220109_090357_Version11Changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%emails}}', 'template', $this->string(255)->after('key'));
        $this->dropColumn('{{%emails_logs}}', 'content');
        $this->dropColumn('{{%emails}}', 'subject');
        $this->dropColumn('{{%emails}}', 'body');
        $this->dropColumn('{{%emails}}', 'attachements');
        $this->createTable('{{%emails_attachements}}', [
            'id' => $this->primaryKey(),
            'message_id' => $this->integer(11)->notNull(),
            'attachements' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->addForeignKey('emails_attachements_message_id_fk', '{{%emails_attachements}}', ['message_id'], '{{%systemmessages}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%emails}}', 'template');
        $this->addColumn('{{%emails_logs}}', 'content', $this->binary()->after('user_id'));
        $this->addColumn('{{%emails}}', 'subject', $this->string(255)->defaultValue('')->after('heading'));
        $this->addColumn('{{%emails}}', 'body', $this->longText()->defaultValue('')->after('subject'));
        $this->dropTableIfExists('{{%emails_attachements%}}');
    }
}
