<?php

declare(strict_types = 1);

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var $this         \yii\web\View
 * @var $dataProvider \yii\data\ActiveDataProvider
 */

$this->title = 'Статистика ссылок';
$qrUrl       = Url::to(['stat/qr']);
?>

  <div class="stat-index">
    <div class="page-header">
      <h1><?=Html::encode($this->title)?></h1>
      <p class="text-muted">Общая статистика по всем коротким ссылкам</p>
    </div>

    <?=GridView::widget([
        'dataProvider'     => $dataProvider,
        'columns'          => [
            [
                'attribute' => 'short_code',
                'label'     => 'Короткий код',
                'format'    => 'raw',
                'value'     => function ($model) {
                  $url = Yii::$app->request->hostInfo . '/' . $model->short_code;

                  return Html::a(
                      Html::encode($model->short_code),
                      $url,
                      [
                          'target' => '_blank',
                          'title'  => 'Перейти по ссылке',
                      ],
                  );
                },
            ],

            [
                'attribute'      => 'original_url',
                'label'          => 'Оригинальный URL',
                'format'         => 'raw',
                'contentOptions' => ['style' => 'max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'],
                'value'          => function ($model) {
                  $url = Html::encode($model->original_url);

                  return Html::a(
                      $url,
                      $model->original_url,
                      [
                          'target' => '_blank',
                          'title'  => $url,
                      ],
                  );
                },
            ],
            [
                'attribute'      => 'click_count',
                'label'          => 'Счетчик кликов',
                'headerOptions'  => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute'      => 'access_count',
                'label'          => 'Записей в логе',
                'headerOptions'  => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'value'          => function ($model) {
                  return $model->access_count ?? 0;
                },
            ],
            [
                'attribute'     => 'created_at',
                'label'         => 'Создана',
                'format'        => 'datetime',
                'headerOptions' => ['style' => 'width: 120px;'],
            ],
            [
                'class'          => 'yii\grid\ActionColumn',
                'header'         => 'Действия',
                'headerOptions'  => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'template'       => '{qr} {view}',
                'buttons'        => [
                    'qr'   => function ($url, $model) {
                      return Html::button('📷 QR', [
                          'class'     => 'btn btn-warning btn-sm btn-qr',
                          'data-id'   => $model->id,
                          'data-code' => Html::encode($model->short_code),
                          'title'     => 'Показать QR-код',
                      ]);
                    },
                    'view' => function ($url, $model) {
                      return Html::a(
                          '📋 Логи',
                          ['stat/view', 'short_url_id' => $model->id],
                          [
                              'class' => 'btn btn-info btn-sm',
                              'title' => 'Просмотр логов',
                          ],
                      );
                    },
                ],
            ],
        ],
        'pager'            => [
            'class'                => 'yii\widgets\LinkPager',
            'options'              => ['class' => 'pagination justify-content-center'],
            'linkOptions'          => ['class' => 'page-link'],
            'activePageCssClass'   => 'active',
            'disabledPageCssClass' => 'disabled',
            'pageCssClass'         => 'page-item',
        ],
        'tableOptions'     => ['class' => 'table table-striped table-bordered table-hover'],
        'headerRowOptions' => ['class' => 'table-light'],
        'emptyText'        => 'Пока нет созданных ссылок.',
        'emptyTextOptions' => ['class' => 'text-center text-muted py-4'],
        'summary'          => 'Показано {begin}-{end} из {totalCount}',
        'layout'           => "{summary}\n{items}{summary}\n{pager}",
    ])?>
    <!-- Модальное окно для QR-кода -->
    <div class='modal fade' id='qrModal' tabindex='-1' aria-hidden='true'>
      <div class='modal-dialog modal-dialog-centered'>
        <div class='modal-content'>
          <div class='modal-header'>
            <h5 class='modal-title'>QR-код для ссылки</h5>
            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
          </div>
          <div class='modal-body text-center'>
            <div id='qr-loading' class='py-4'>
              <div class='spinner-border text-primary' role='status'>
                <span class='visually-hidden'>Загрузка...</span>
              </div>
              <p class='mt-2 text-muted'>Генерация QR-кода...</p>
            </div>

            <div id='qr-content' style='display: none;'>
              <div class='mb-3'>
                <label class='form-label text-muted'>Короткая ссылка:</label>
                <div class='input-group'>
                  <input type='text' id='qr-short-url' class='form-control text-center' readonly>
                  <button class='btn btn-outline-secondary' type='button' id='qr-copy-btn'>
                    📋
                  </button>
                </div>
              </div>

              <div class='qr-container p-3 bg-light rounded mb-3'>
                <img id='qr-image' class='img-fluid' style='max-width: 250px;' src='' alt='QR Code'>
              </div>

              <div class='text-muted small'>
                <span id='qr-original-url'></span>
              </div>
            </div>

            <div id='qr-error' class='alert alert-danger' style='display: none;'></div>
          </div>
          <div class='modal-footer'>
            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Закрыть</button>
            <a id='qr-visit-link' href='#' target='_blank' class='btn btn-primary'>Перейти по ссылке</a>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
$this->registerJs(
    <<<JS
      const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
      
      $(document).on('click', '.btn-qr', function() {
          const id = $(this).data('id');
          const code = $(this).data('code');
      
          $('#qr-loading').show();
          $('#qr-content').hide();
          $('#qr-error').hide();
          $('#qr-visit-link').attr('href', '#');
      
          // Открываем модальное окно
          qrModal.show();
      
          // Загружаем QR-код
          $.ajax({
              url: '{$qrUrl}',
              type: 'GET',
              data: {id: id},
              dataType: 'json',
              success: function(response) {
                  $('#qr-loading').hide();
            
                  if (response.success) {
                      $('#qr-short-url').val(response.short_url);
                      $('#qr-image').attr('src', response.qr_code);
                      $('#qr-original-url').text(response.original_url);
                      $('#qr-visit-link').attr('href', response.short_url);
                      $('#qr-content').fadeIn();
                  } else {
                      $('#qr-error').text(response.message || 'Ошибка загрузки').show();
                  }
              },
              error: function() {
                  $('#qr-loading').hide();
                  $('#qr-error').text('Ошибка сервера').show();
              }
          });
      });
      
      // Копирование ссылки из модального окна
      $(document).on('click', '#qr-copy-btn', async function() {
          const input = document.getElementById('qr-short-url');
          const btn = $(this);
      
          try {
              await navigator.clipboard.writeText(input.value);
              btn.html('✅');
              setTimeout(() => btn.html('📋'), 2000);
          } catch (err) {
              input.select();
              document.execCommand('copy');
              btn.html('✅');
              setTimeout(() => btn.html('📋'), 2000);
          }
      });
      JS,
);
?>