yii2-oauth2-server
==================

A wrapper for implementing an OAuth2 Server(https://github.com/bshaffer/oauth2-server-php)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist myworkup/yii2-oauth2-server "*"
```

or add

```json
"myworkup/yii2-oauth2-server": "~2.1"
```

to the require section of your composer.json.

To use this extension,  simply add the following code in your application configuration:

```php
'oauth2' => [
    'class' => 'myworkup\yii2\oauth2server\Module',
    'tokenParamName' => 'accessToken',
    'tokenAccessLifetime' => 3600 * 24,
    'storageMap' => [
        'user_credentials' => 'common\models\User',
    ],
    'grantTypes' => [
        'user_credentials' => [
            'class' => 'OAuth2\GrantType\UserCredentials',
        ],
        'refresh_token' => [
            'class' => 'OAuth2\GrantType\RefreshToken',
            'always_issue_new_refresh_token' => true
        ]
    ]
]
```

```common\models\User``` - user model implementing an interface ```\OAuth2\Storage\UserCredentialsInterface```, so the oauth2 credentials data stored in user table

The next step your shold run migration

```php
yii migrate --migrationPath=@vendor/myworkup/yii2-oauth2-server/migrations
```

this migration create the oauth2 database scheme and insert test user credentials ```testclient:testpass``` for ```http://fake/```

add url rule to urlManager

```php
'urlManager' => [
    'rules' => [
        'POST oauth2/<action:\w+>' => 'oauth2/default/<action>',
        ...
    ]
]
```

Usage
-----

To use this extension,  simply add the behaviors for your base controller:

```php
use yii\helpers\ArrayHelper;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use myworkup\yii2\oauth2server\filters\ErrorToExceptionFilter;
use myworkup\yii2\oauth2server\filters\auth\CompositeAuth;

class Controller extends \yii\rest\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    ['class' => HttpBearerAuth::className()],
                    ['class' => QueryParamAuth::className(), 'tokenParam' => 'accessToken'],
                ]
            ],
            'exceptionFilter' => [
                'class' => ErrorToExceptionFilter::className()
            ],
        ]);
    }
}
```

For more, see https://github.com/bshaffer/oauth2-server-php
