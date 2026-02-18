<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\UrlForm;
use app\services\UrlShortenerService;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class SiteController extends Controller
{
    private UrlShortenerService $urlService;

    public function __construct($id, $module, UrlShortenerService $urlService, $config = [])
    {
        $this->urlService = $urlService;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
          'model' => new UrlForm()
        ]);
    }

    public function actionShorten(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $form = new UrlForm();

        if (!$form->load(Yii::$app->request->post()) || !$form->validate()) {
            return $this->errorResponse('Некорректный URL');
        }

        try {
            $result = $this->urlService->createShortUrl($form->url);
            $qrCode = $this->urlService->generateQrCode($result['shortUrl']);

            return [
              'success' => true,
              'short_url' => $result['shortUrl'],
              'qr_code' => 'data:image/svg+xml;base64,' . base64_encode($qrCode),
            ];
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return $this->errorResponse('Внутренняя ошибка сервера');
        }
    }

    /**
     * @param string $code
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionRedirect(string $code): Response
    {
        try {
            $shortUrl = $this->urlService->processRedirect($code);
            return $this->redirect($shortUrl->original_url, 301);
        } catch (NotFoundHttpException $e) {
            throw $e;
        }
    }

    private function errorResponse(string $message): array
    {
        return [
          'success' => false,
          'message' => $message,
        ];
    }
}