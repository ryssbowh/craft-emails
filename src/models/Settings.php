<?php

namespace Ryssbowh\CraftEmails\models;

use craft\base\Model;
use craft\helpers\FileHelper;

class Settings extends Model
{
    /**
     * @var string
     */
    public string $menuItemName = '';

    /**
     * @var boolean
     */
    public bool $compressLogs = true;

    /**
     * @var string
     */
    public string $mailchimpApiKey = '';

    /**
     * @var integer
     */
    public int $mailchimpCacheDuration = 60;

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        return [
            [['menuItemName', 'mailchimpApiKey'], 'string'],
            ['compressLogs', 'boolean'],
            ['mailchimpCacheDuration', 'integer']
        ];
    }
}
