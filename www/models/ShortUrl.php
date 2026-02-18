<?php

declare(strict_types = 1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int         $id
 * @property string      $original_url
 * @property string      $short_code
 * @property int         $created_at
 * @property int         $click_count
 * @property int         $access_count
 *
 * @property AccessLog[] $accessLogs
 */
class ShortUrl extends ActiveRecord
{
    public ?int $access_count = null;

    public static function tableName(): string
    {
        return '{{%short_urls}}';
    }

    public function rules(): array
    {
        return [
          [['original_url', 'short_code'], 'required'],
          [['original_url'], 'string'],
          [['created_at', 'click_count'], 'integer'],
          [['short_code'], 'string', 'max' => 10],
          [['short_code'], 'unique'],
          [['original_url'], 'url', 'validSchemes' => ['http', 'https']],
          [['access_count'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
          'id'           => 'ID',
          'original_url' => 'Оригинальный URL',
          'short_code'   => 'Короткий код',
          'created_at'   => 'Дата создания',
          'click_count'  => 'Количество кликов',
          'access_count' => 'Записей в логе',
        ];
    }

    public function behaviors(): array
    {
        return [
          [
            'class'              => TimestampBehavior::class,
            'createdAtAttribute' => 'created_at',
            'updatedAtAttribute' => false,
          ],
        ];
    }

    public function getAccessLogs(): \yii\db\ActiveQuery
    {
        return $this->hasMany(AccessLog::class, ['short_url_id' => 'id']);
    }

    /**
     * Регистрирует клик и создает лог
     *
     * @return bool
     * @throws \Exception
     */
    public function registerClick(): bool
    {
        $this->click_count++;

        if (!$this->save(false, ['click_count'])) {
            throw new \Exception('Failed to update click count');
        }

        $log               = new AccessLog();
        $log->short_url_id = $this->id;

        if (!$log->save()) {
            throw new \Exception('Failed to save access log: ' . implode(', ', $log->firstErrors));
        }

        return true;
    }

    public static function findByShortCode(string $code): ?self
    {
        return static::findOne(['short_code' => $code]);
    }
}