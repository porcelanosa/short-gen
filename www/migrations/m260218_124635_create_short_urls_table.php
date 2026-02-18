<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%short_urls}}`.
 */
class m260218_124635_create_short_urls_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%short_urls}}', [
            'id' => $this->primaryKey(),
            'original_url' => $this->text()->notNull(),
            'short_code' => $this->string(10)->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
            'click_count' => $this->integer()->defaultValue(0),
          ]);

        $this->createIndex('idx-short_code', '{{%short_urls}}', 'short_code');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%short_urls}}');
    }
}
