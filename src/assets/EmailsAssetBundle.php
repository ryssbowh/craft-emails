<?php

namespace Ryssbowh\CraftEmails\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class EmailsAssetBundle extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';

    public $css = [
        'emails.css'
    ];

    public $js = [
        'preview.js'
    ];

    public $depends = [
        CpAsset::class
    ];
}