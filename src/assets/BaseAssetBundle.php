<?php

namespace Ryssbowh\CraftEmails\assets;

use craft\web\AssetBundle;
use craft\web\View;
use craft\web\assets\cp\CpAsset;

class BaseAssetBundle extends AssetBundle
{
    public $sourcePath = __DIR__ . '/dist';

    public $depends = [
        CpAsset::class,
        CraftEmailsAssetBundle::class
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->_registerTranslations($view);
        }
    }

    protected function _registerTranslations($view)
    {
        $messages = require \Craft::getAlias('@Ryssbowh/CraftEmails/translations/en-GB/emails.php');
        $messages = array_keys($messages);
        $view->registerTranslations('emails', $messages);
    }
}