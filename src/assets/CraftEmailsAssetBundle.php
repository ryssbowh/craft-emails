<?php

namespace Ryssbowh\CraftEmails\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CraftEmailsAssetBundle extends AssetBundle
{
    public $sourcePath = __DIR__ . '/dist';

    public $depends = [
        CpAsset::class
    ];

    public $js = [
        'craftemails.js'
    ];
}