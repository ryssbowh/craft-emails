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
            'content' => $this->binary(),
            'email' => $this->string(255),
            'bcc' => $this->string(255),
            'cc' => $this->string(255),
            'subject' => $this->string(255),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        
        $this->addForeignKey('emails_logs_email_id_fk', '{{%emails_logs}}', ['email_id'], '{{%emails}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%emails_logs}}');
        $this->dropTableIfExists('{{%emails}}');
    }
}
