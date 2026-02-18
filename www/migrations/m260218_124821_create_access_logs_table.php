<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%access_logs}}`.
 */
class m260218_124821_create_access_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%access_logs}}', [

          'id' => $this->primaryKey(),
          'short_url_id' => $this->integer()->notNull(),
          'ip_address' => $this->string(45)->notNull(),
          'user_agent' => $this->string(255),
          'accessed_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
          'fk-access_logs-short_url_id',
          '{{%access_logs}}',
          'short_url_id',
          '{{%short_urls}}',
          'id',
          'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%access_logs}}');
    }
}
