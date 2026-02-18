<?php

declare(strict_types = 1);

namespace app\controllers;

use app\models\AccessLog;
use app\models\ShortUrl;
use app\services\UrlShortenerService;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class StatController extends Controller
{
    private UrlShortenerService $urlService;

    public function __construct($id, $module, UrlShortenerService $urlService, $config = [])
    {
        $this->urlService = $urlService;
        parent::__construct($id, $module, $config);
    }

    /**
     * Список всех коротких ссылок со статистикой
     * URL: /stats
     */
    public function actionIndex(): string
    {
        $query = ShortUrl::find()
                         ->select(['short_urls.*', 'access_count' => 'COUNT(access_logs.id)'])
                         ->leftJoin('access_logs', 'access_logs.short_url_id = short_urls.id')
                         ->groupBy('short_urls.id');

        $dataProvider = new ActiveDataProvider([
          'query'      => $query,
          'pagination' => [
            'pageSize' => 20,
          ],
          'sort'       => [
            'defaultOrder' => [
              'created_at' => SORT_DESC,
            ],
            'attributes'   => [
              'id',
              'original_url',
              'short_code',
              'click_count',
              'created_at',
              'access_count' => [
                'asc'     => ['access_count' => SORT_ASC],
                'desc'    => ['access_count' => SORT_DESC],
                'default' => SORT_DESC,
                'label'   => 'Записей в логе',
              ],
            ],
          ],
        ]);

        return $this->render('index', [
          'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Детальная статистика по конкретной ссылке
     * URL: /stats/{short_url_id}
     *
     * @return string
     *
     * @param  int  $short_url_id
     *
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionView(int $short_url_id): string
    {
        $shortUrl = ShortUrl::findOne($short_url_id);

        if (!$shortUrl) {
            throw new NotFoundHttpException('Ссылка не найдена.');
        }

        $dataProvider = new ActiveDataProvider([
          'query'      => AccessLog::find()
                                   ->where(['short_url_id' => $short_url_id])
                                   ->orderBy(['accessed_at' => SORT_DESC]),
          'pagination' => [
            'pageSize' => 50,
          ],
          'sort'       => [
            'defaultOrder' => [
              'accessed_at' => SORT_DESC,
            ],
          ],
        ]);

        return $this->render('view', [
          'shortUrl'     => $shortUrl,
          'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * AJAX: Генерация QR-кода для существующей ссылки
     */
    public function actionQr(int $id): array
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $shortUrl = ShortUrl::findOne($id);

        if (!$shortUrl) {
            return ['success' => false, 'message' => 'Ссылка не найдена'];
        }

        try {
            $fullUrl = \Yii::$app->request->hostInfo . '/' . $shortUrl->short_code;
            $qrCode  = $this->urlService->generateQrCode($fullUrl, 350);

            return [
              'success'      => true,
              'qr_code'      => 'data:image/svg+xml;base64,' . base64_encode($qrCode),
              'short_url'    => $fullUrl,
              'original_url' => $shortUrl->original_url,
            ];
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());

            return ['success' => false, 'message' => 'Ошибка генерации QR-кода'];
        }
    }
}