# Hejiang Storage

Thanks for your attention. This package is only used for my company projects, please do NOT use it in your product environment.

## Usage

Add this lines of code to your Yii application config:

```php
'components' => [
    'class' => 'Hejiang\Storage\Components\StorageComponent',
    'basePath' => 'temp/',
    'driver' => [
        'class' => 'Hejiang\Storage\Drivers\Local',
        'accessKey' => '',
        'secretKey' => '',
        'bucket' => '',
        //'urlTemplate' => '?style',
    ]
]
```

Then after app bootstarpping, use:

```php
// get storage component instance
$storage = \Yii::$app->storage;
// fetch uploaded file by field name
$file = $storage->getUploadedFile('FILE-FIELD-NAME');

// save...
$res = $file->saveAs('A-NEW-FILE-NAME.EXT');
// or
$res = $file->saveWithOriginalExtension('A-NEW-FILE-BASE-NAME');
// or
$res = $file->saveAsUniqueHash();
```

The result `$res` returns a URL string which can access this file.

## About

Working at: [Zhejiang Hejiang Technology Co., Ltd.](http://www.zjhejiang.com/)