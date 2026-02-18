<?php

declare(strict_types = 1);

use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var $this         \yii\web\View
 * @var $shortUrl     \app\models\ShortUrl
 * @var $dataProvider \yii\data\ActiveDataProvider
 */

$this->title                   = 'Логи ссылки: ' . $shortUrl->short_code;
$this->params['breadcrumbs'][] = ['label' => 'Статистика', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="stat-view">
    <div class="page-header">
        <h1><?=Html::encode($this->title)?></h1>
    </div>

    <!-- Информация о ссылке -->
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">Информация о ссылке</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%;">ID</th>
                            <td><?=$shortUrl->id?></td>
                        </tr>
                        <tr>
                            <th>Короткий код</th>
                            <td>
                                <?=Html::a(
                                  Html::encode($shortUrl->short_code),
                                  Yii::$app->request->hostInfo . '/' . $shortUrl->short_code,
                                  ['target' => '_blank'],
                                )?>
                            </td>
                        </tr>
                        <tr>
                            <th>Оригинальный URL</th>
                            <td style="word-break: break-all;">
                                <?=Html::a(
                                  Html::encode($shortUrl->original_url),
                                  $shortUrl->original_url,
                                  ['target' => '_blank'],
                                )?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%;">Всего кликов</th>
                            <td>
                                <span class="badge badge-primary" style="font-size: 16px;">
                                    <?=$shortUrl->click_count?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Записей в логе</th>
                            <td>
                                <span class="badge badge-info" style="font-size: 16px;">
                                    <?=$dataProvider->totalCount?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Создана</th>
                            <td><?=Yii::$app->formatter->asDatetime($shortUrl->created_at)?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">История переходов</h3>
        </div>
        <div class="panel-body">
            <?=GridView::widget([
              'dataProvider'     => $dataProvider,
              'columns'          => [
                ['class' => 'yii\grid\SerialColumn'],

                [
                  'attribute'     => 'id',
                  'headerOptions' => ['style' => 'width: 60px;'],
                ],

                [
                  'attribute'     => 'ip_address',
                  'label'         => 'IP адрес',
                  'headerOptions' => ['style' => 'width: 130px;'],
                  'value'         => function ($model) {
                      return Html::encode($model->ip_address);
                  },
                ],

                [
                  'attribute'      => 'user_agent',
                  'label'          => 'User Agent',
                  'contentOptions' => ['style' => 'max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'],
                  'value'          => function ($model) {
                      return $model->user_agent ? Html::encode($model->user_agent) : '<span class="text-muted">(не указан)</span>';
                  },
                  'format'         => 'raw',
                ],

                [
                  'attribute'     => 'accessed_at',
                  'label'         => 'Дата и время',
                  'format'        => 'datetime',
                  'headerOptions' => ['style' => 'width: 150px;'],
                ],
              ],
              'pager'            => [
                'class'   => 'yii\widgets\LinkPager',
                'options' => ['class' => 'pagination justify-content-center'],
              ],
              'tableOptions'     => ['class' => 'table table-striped table-bordered'],
              'emptyText'        => 'Пока нет записей о переходах по этой ссылке.',
              'emptyTextOptions' => ['class' => 'text-center text-muted'],
              'summary' => 'Показано {begin}-{end} из {totalCount}',
            ])?>
        </div>
    </div>

    <div class="form-group">
        <?=Html::a('<span class="glyphicon glyphicon-arrow-left"></span>&leftarrow;Назад к списку', ['index'], ['class' => 'btn btn-default'])?>
    </div>
</div>