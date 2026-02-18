<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $short_url_id
 * @property string $ip_address
 * @property string|null $user_agent
 * @property int $accessed_at
 *
 * @property ShortUrl $shortUrl
 */
class AccessLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%access_logs}}';
    }

    public function init(): void
    {
        parent::init();
        if (\Yii::$app->has('request')) {
            $this->ip_address = $this->ip_address ?? \Yii::$app->request->userIP ?? 'unknown';
            $this->user_agent = $this->user_agent ?? \Yii::$app->request->userAgent;
        }
    }

    public function rules(): array
    {
        return [
          [['short_url_id', 'ip_address'], 'required'],
          [['short_url_id', 'accessed_at'], 'integer'],
          [['ip_address'], 'string', 'max' => 45],
          [['user_agent'], 'string', 'max' => 255],
          [
            ['short_url_id'],
            'exist',
            'skipOnError' => true,
            'targetClass' => ShortUrl::class,
            'targetAttribute' => ['short_url_id' => 'id']
          ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
          'id' => 'ID',
          'short_url_id' => 'ID короткой ссылки',
          'ip_address' => 'IP адрес',
          'user_agent' => 'User Agent',
          'accessed_at' => 'Дата доступа',
        ];
    }

    public function behaviors(): array
    {
        return [
          [
            'class' => TimestampBehavior::class,
            'createdAtAttribute' => 'accessed_at',
            'updatedAtAttribute' => false,
          ],
        ];
    }

    public function getShortUrl(): ActiveQuery
    {
        return $this->hasOne(ShortUrl::class, ['id' => 'short_url_id']);
    }
}