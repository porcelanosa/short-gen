<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/**
 * @var $this \yii\web\View
 * @var $model \app\models\UrlForm
 */

$this->title = 'Сервис коротких ссылок';
$shortenUrl = Url::to(['site/shorten']);
?>

    <div class="site-index">
        <div class="jumbotron text-center">
            <h1>Создайте короткую ссылку</h1>
            <p class="lead">Вставьте длинную ссылку и получите короткую версию + QR-код</p>
        </div>

        <div class="body-content">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php $form = ActiveForm::begin([
                            'id' => 'shorten-form',
                            'options' => ['class' => 'form-inline justify-content-center'],
                            'enableAjaxValidation' => false,
                            'enableClientValidation' => false,
                            'validateOnSubmit' => false,
                            'action' => false,
                    ]); ?>

                    <div class="input-group " style="width: 100%;">
                        <?= $form->field($model, 'url', [
                                'template' => '{input}',
                                'options' => ['tag' => false],
                        ])->textInput([
                                'class' => 'form-control',
                                'placeholder' => 'https://example.ru/very-long-url...',
                                'autocomplete' => 'off',
                        ])->label(false) ?>

                        <div class="input-group-append">
                            <?= Html::submitButton('✂️ Сократить', [
                                    'class' => 'btn btn-primary',
                                    'id' => 'shorten-btn',
                            ]) ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'url', [
                            'template' => '<div class="text-danger mt-2">{error}</div>',
                            'options' => ['tag' => false],
                    ])->label(false) ?>

                    <?php ActiveForm::end(); ?>

                    <!-- Результат -->
                    <div id="result-container" class="mt-5" style="display:none;">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0">Готово!</h4>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <label class="text-muted">Короткая ссылка:</label>
                                    <div class="input-group">
                                        <input type="text" id="short-url-input" class="form-control form-control-lg text-center" readonly>
                                        <div class="input-group-append input-group-lg">
                                            <button class="btn btn-outline-secondary copy-btn" type="button">
                                              📑 &nbsp;Копировать
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="text-muted">QR-код для мобильных устройств:</label>
                                    <div class="qr-container p-3 bg-light rounded">
                                        <img id="qr-code" class="img-fluid" style="max-width: 200px;" src="" alt="QR Code">
                                    </div>
                                    <small class="text-muted d-block mt-2">Наведите камеру телефона для перехода по ссылке</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ошибки -->
                    <div id="error-container" class="mt-4" style="display:none;">
                        <div class="alert alert-danger">
                            <span id="error-message"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$this->registerJs(<<<JS
    $('#shorten-form').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        const form = $(this);
        const btn = $('#shorten-btn');
        const originalText = btn.text();
        
        btn.prop('disabled', true).text('Обработка...');
        
        $.ajax({
            url: '{$shortenUrl}',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#short-url-input').val(response.short_url);
                    $('#qr-code').attr('src', response.qr_code);
                    $('#result-container').fadeIn();
                    $('#error-container').hide();
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Ошибка сервера. Попробуйте позже.';
                showError(msg);
            },
            complete: function() {
              console.log(1, originalText, btn.text());
                btn.prop('disabled', false).text(originalText);
              console.log(2, originalText, btn.text());
            }
        });
        
        return false;
    });
    
    function showError(message) {
        $('#error-message').text(message);
        $('#error-container').fadeIn();
        $('#result-container').hide();
    }
    
    $(document).on('click', '.copy-btn', async function() {
        const input = document.getElementById('short-url-input');
        const btn = $(this);
        const originalHTML = btn.html();
        
        try {
            await navigator.clipboard.writeText(input.value);
            btn.html('Скопировано');
            setTimeout(() => btn.html(originalHTML), 2000);
        } catch (err) {
            input.select();
            input.setSelectionRange(0, 99999); // Для мобильных
            try {
                document.execCommand('copy');
                btn.html('Скопировано');
                setTimeout(() => btn.html(originalHTML), 2000);
            } catch (e) {
                btn.html('<span style="font-weight: bold">Ошибка</span>');
                setTimeout(() => btn.html(originalHTML), 2000);
            }
        }
    });
JS
);
?>