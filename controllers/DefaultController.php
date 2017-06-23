<?php

namespace mobilejazz\yii2\oauth2server\controllers;

use mobilejazz\yii2\oauth2server\filters\auth\CompositeAuth;
use mobilejazz\yii2\oauth2server\filters\ErrorToExceptionFilter;
use mobilejazz\yii2\oauth2server\models\OauthAccessTokens;
use Yii;
use yii\helpers\ArrayHelper;

class DefaultController extends \yii\rest\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = ArrayHelper::merge(parent::behaviors(),
            [
                'exceptionFilter' => [
                    'class' => ErrorToExceptionFilter::className(),
                ],
            ]);

        $behaviors['authenticator'] = [
            'class'  => CompositeAuth::className(),
            'except' => ['token'],
        ];

        return $behaviors;
    }

    public function actionToken()
    {
        $server = $this->module->getServer();
        $request = $this->module->getRequest();
        $response = $server->handleTokenRequest($request);

        //Set cache control headers
        Yii::$app->response->headers['Cache-Control'] = 'no-cache';
        Yii::$app->response->headers['Pragma'] = 'no-cache';


        return $response->getParameters();
    }

    public function actionRevoke()
    {
        $server = $this->module->getServer();
        $request = $this->module->getRequest();
        $response = $server->handleRevokeRequest($request);

        return $response->getParameters();
    }


    public function actionIntrospect()
    {
        if (!Yii::$app->request->post('token')) {
            $message = Yii::t('oauth2server', 'Missing parameter: "token" is required');
            if ($message === null) {
                $message = Yii::t('yii', 'An internal server error occurred.');
            }
            throw new \yii\web\HttpException(400, $message);
        }

        $response["active"] = false;

        $token = OauthAccessTokens::findOne(["access_token" => Yii::$app->request->post('token')]);
        if ($token) {
            $expires = strtotime($token->expires);
            if (time() < $expires) {
                $response["active"] = true;
            }
            $response["scope"] = $token->scope;
            $response["user_id"] = $token->user_id;
            $response["client_id"] = $token->client_id;
            $response["exp"] = $expires;
        }

        return $response;
    }
}
