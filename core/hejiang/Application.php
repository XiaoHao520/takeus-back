<?php

namespace app\hejiang;

/**
 * Hejiang Application
 *
 * @property \Raven_Client $sentry
 * @property Serializer $serializer
 */
class Application extends \yii\web\Application
{
    public function __construct($configFile = '/config/web.php')
    {
        $this->loadDotEnv();
        $this->defineConstants();

        $basePath = dirname(__DIR__);
        require $basePath . '/vendor/yiisoft/yii2/Yii.php';

        $this->loadYiiHelpers();
        parent::__construct(require $basePath . $configFile);

        $this->enableJsonResponse();
        $this->enableErrorReporting();
    }

    /**
     * Load .env file
     *
     * @return void
     */
    protected function loadDotEnv()
    {
        try {
            $dotenv = new \Dotenv\Dotenv(dirname(__DIR__));
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidPathException $ex) {
        }
    }

    /**
     * Define some constants
     *
     * @return void
     */
    protected function defineConstants()
    {
        define_once('WE7_MODULE_NAME', 'zjhj_mall');
        define_once('IN_IA', true);
        $this->defineEnvConstants(['YII_DEBUG', 'YII_ENV']);
    }

    /**
     * Define some constants via `env()`
     *
     * @param array $names
     * @return void
     */
    protected function defineEnvConstants($names = [])
    {
        foreach ($names as $name) {
            if ((!defined($name)) && ($value = env($name))) {
                define($name, $value);
            }
        }
    }

    /**
     * Override yii helper classes
     *
     * @return void
     */
    protected function loadYiiHelpers()
    {
        \Yii::$classMap['yii\helpers\FileHelper'] = '@app/hejiang/FileHelper.php';
    }

    /**
     * Enable JSON response if app returns Array or Object
     *
     * @return void
     */
    protected function enableJsonResponse()
    {
        $this->response->on(\yii\web\Response::EVENT_BEFORE_SEND,
            function ($event) {
                /* @var $response \yii\web\Response */
                $response = $event->sender;
                if (is_array($response->data) || is_object($response->data)) {
                    $response->format = \yii\web\Response::FORMAT_JSON;
                }
            }
        );
    }

    /**
     * Enable full error reporting if using debug mode
     *
     * @return void
     */
    protected function enableErrorReporting()
    {
        if (YII_DEBUG) {
            error_reporting(E_ALL ^ E_NOTICE);
        }
    }
}
