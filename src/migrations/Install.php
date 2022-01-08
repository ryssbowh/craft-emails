<?php

namespace Ryssbowh\CraftEmails\migrations;

use Craft; 
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%emails}}', [
            'id' => $this->primaryKey(),
            'key' => $this->string(255)->notNull(),
            'system' => $this->boolean()->defaultValue(false),
            'redactorConfig' => $this->string(255),
            'heading' => $this->string(255)->notNull(),
            'subject' => $this->string(255)->defaultValue(''),
            'body' => $this->longText()->defaultValue(''),
            'instructions' => $this->text()->defaultValue(''),
            'attachements' => $this->text()->defaultValue(''),
            'from' => $this->string(255),
            'fromName' => $this->string(255),
            'replyTo' => $this->string(255),
            'bcc' => $this->string(255),
            'cc' => $this->string(255),
            'plain' => $this->boolean()->defaultValue(false),
            'saveLogs' => $this->boolean(),
            'sent' => $this->integer(11)->defaultValue(0),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%emails_logs}}', [
            'id' => $this->primaryKey(),
            'email_id' => $this->integer(11),
            'user_id' => $this->integer(11),
            'content' => $this->binary(),
            'to' => $this->longText(),
            'bcc' => $this->longText(),
            'cc' => $this->longText(),
            'subject' => $this->string(255),
            'from' => $this->string(255),
            'replyTo' => $this->string(255),
            'attachements' => $this->longText(),
            'is_console' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%emails_shots}}', [
            'id' => $this->primaryKey(),
            'handle' => $this->string(255),
            'name' => $this->string(255),
            'email_id' => $this->integer(11),
            'users' => $this->longText(),
            'emails' => $this->longText(),
            'sources' => $this->longText(),
            'useQueue' => $this->boolean(),
            'sent' => $this->integer(11)->defaultValue(0),
            'saveLogs' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%emails_shots_logs}}', [
            'id' => $this->primaryKey(),
            'shot_id' => $this->integer(11)->null(),
            'emails' => $this->longText(),
            'user_id' => $this->integer(11),
            'is_console' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        
        $this->addForeignKey('emails_logs_email_id_fk', '{{%emails_logs}}', ['email_id'], '{{%emails}}', ['id'], 'CASCADE', null);
        $this->addForeignKey('emails_shots_email_id_fk', '{{%emails_shots}}', ['email_id'], '{{%emails}}', ['id'], 'SET NULL', null);
        $this->addForeignKey('emails_shots_logs_shot_id_fk', '{{%emails_shots_logs}}', ['shot_id'], '{{%emails_shots}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%emails_shots_logs}}');
        $this->dropTableIfExists('{{%emails_logs}}');
        $this->dropTableIfExists('{{%emails_shots}}');
        $this->dropTableIfExists('{{%emails}}');
    }
}
