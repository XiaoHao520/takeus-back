<?php

namespace Hejiang\Storage\Drivers;

class BaseDriver extends \yii\base\Component
{
    public $bucket = '';

    public $accessKey = '';

    public $secretKey = '';

    public $urlTemplate = '';

    public $urlCompenents = null;

    public function init()
    {
        parent::init();
        try {
            $this->urlCompenents = parse_url($this->urlTemplate);
        } catch (\Exception $ex) {

        }
    }

    public function getAccessUrl($path, $default = '')
    {
        if (is_array($this->urlCompenents)) {
            $defaultCompenents = parse_url($default);
            $finalComponents = array_merge($defaultCompenents, $this->urlCompenents);
            $finalComponents['path'] = '/' . trim($path, '/');
            return static::buildUrl($finalComponents);
        } else {
            return $default;
        }
    }

    public static function buildUrl($parts)
    {
        return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') . ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') . (isset($parts['user']) ? "{$parts['user']}" : '') . (isset($parts['pass']) ? ":{$parts['pass']}" : '') . (isset($parts['user']) ? '@' : '') . (isset($parts['host']) ? "{$parts['host']}" : '') . (isset($parts['port']) ? ":{$parts['port']}" : '') . (isset($parts['path']) ? "{$parts['path']}" : '') . (isset($parts['query']) ? "?{$parts['query']}" : '') . (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }
}