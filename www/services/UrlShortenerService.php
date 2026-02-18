<?php

declare(strict_types = 1);

namespace app\services;

use app\models\AccessLog;
use app\models\ShortUrl;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class UrlShortenerService extends Component
{
    private const int CODE_LENGTH      = 6;
    private const string CODE_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const int    CURL_TIMEOUT = 5;
    private const int    QR_SIZE      = 200;

    /**
     * Создает короткую ссылку
     *
     * @return array{shortCode: string, shortUrl: string}
     *
     * @param  string  $originalUrl
     *
     * @throws \Exception
     */
    public function createShortUrl(string $originalUrl): array
    {
        if (!$this->isUrlAccessible($originalUrl)) {
            throw new \RuntimeException('Ресурс недоступен');
        }

        $shortCode = $this->generateUniqueCode();

        $model               = new ShortUrl();
        $model->original_url = $originalUrl;
        $model->short_code   = $shortCode;

        if (!$model->save()) {
            throw new \RuntimeException('Ошибка сохранения: ' . implode(', ', $model->firstErrors));
        }

        return [
          'shortCode' => $shortCode,
          'shortUrl'  => $this->buildShortUrl($shortCode),
        ];
    }

    /**
     * Проверяет доступность URL
     */
    public function isUrlAccessible(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
          CURLOPT_NOBODY         => true,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_TIMEOUT        => self::CURL_TIMEOUT,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_RETURNTRANSFER => true,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            \Yii::warning("CURL error for $url: $error");
        }

        return $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * Генерирует уникальный код
     */
    private function generateUniqueCode(): string
    {
        $attempts    = 0;
        $maxAttempts = 10;

        do {
            $code   = $this->generateRandomCode();
            $exists = ShortUrl::find()->where(['short_code' => $code])->exists();
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($exists) {
            throw new \RuntimeException('Не удалось сгенерировать уникальный код');
        }

        return $code;
    }

    /**
     * Генерирует случайный код
     *
     * @throws \Random\RandomException
     */
    private function generateRandomCode(): string
    {
        $code     = '';
        $maxIndex = strlen(self::CODE_ALPHABET) - 1;

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $maxIndex)];
        }

        return $code;
    }

    /**
     * Строит полный короткий URL
     */
    private function buildShortUrl(string $code): string
    {
        return \Yii::$app->request->hostInfo . '/' . $code;
    }

    /**
     * Генерирует QR-код в формате SVG
     */
    public function generateQrCode(string $url, ?int $qr_size = null ): string
    {
        $renderer = new ImageRenderer(
          new RendererStyle($qr_size??self::QR_SIZE),
          new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    /**
     * Обрабатывает переход по короткой ссылке
     *
     * @throws NotFoundHttpException
     */
    public function processRedirect(string $code): ShortUrl
    {
        $shortUrl = ShortUrl::findByShortCode($code);

        if (!$shortUrl) {
            throw new NotFoundHttpException('Ссылка не найдена или устарела');
        }
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            $shortUrl->registerClick();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            \Yii::error('Failed to log click: ' . $e->getMessage());
        }

        return $shortUrl;
    }
}