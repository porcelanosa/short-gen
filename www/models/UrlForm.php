<?php

declare(strict_types = 1);

namespace app\models;

use yii\base\Model;

class UrlForm extends Model
{
    public string $url;
    public function __construct($config = [])
    {
        $this->url = '';
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
          ['url', 'required', 'message' => 'Введите URL'],
          ['url', 'url', 'defaultScheme' => 'http', 'message' => 'Некорректный URL'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
          'url' => 'URL',
        ];
    }
}