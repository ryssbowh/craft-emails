<?php 

namespace Ryssbowh\CraftEmails\Models;

use craft\base\Model;
use craft\helpers\FileHelper;

class Settings extends Model
{
    /**
     * @var string
     */
    public $menuItemName;

    /**
     * @var boolean
     */
    public $compressLogs = true;

    /**
     * @var string
     */
    public $mailchimpApiKey;

    /**
     * @var integer
     */
    public $mailchimpCacheDuration = 60;

    /**
     * Get all defined redactor configuration files
     * 
     * @return array
     */
    public function getRedactorConfigOptions(): array
    {
        $path = \Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . 'redactor';
        if (is_dir($path)) {
            $files = FileHelper::findFiles($path, [
                'only' => ['*.json'],
                'recursive' => false
            ]);

            foreach ($files as $file) {
                $filename = basename($file);
                $options[$filename] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        ksort($options);
        return $options;
    }
}