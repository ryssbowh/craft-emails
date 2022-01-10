<?php

namespace Ryssbowh\CraftEmails\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PreviewAssetBundle extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';

    public $css = [
        'preview.css'
    ];

    public $depends = [
        CpAsset::class
    ];
}