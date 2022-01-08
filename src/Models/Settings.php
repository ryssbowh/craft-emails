<?php 

namespace Ryssbowh\CraftEmails\Models;

use craft\base\Model;
use craft\helpers\FileHelper;

class Settings extends Model
{
    /**
     * @var array
     */
    public $configDriven = ['heading', 'from', 'fromName', 'replyTo', 'cc', 'bcc'];

    /**
     * @var string
     */
    public $menuItemName;

    /**
     * @var boolean
     */
    public $removeShotDuplicates = true;

    /**
     * Get all parameters that can be considered config
     * 
     * @return array
     */
    public function getConfigDrivenOptions(): array
    {
        return [
            'heading' => \Craft::t('emails', 'Heading'),
            'from' => \Craft::t('emails', 'From'),
            'fromName' => \Craft::t('emails', 'Name from'),
            'replyTo' => \Craft::t('emails', 'Reply to email'),
            'cc' => \Craft::t('emails', 'Cc'),
            'bcc' => \Craft::t('emails', 'Bcc'),
            'subject' => \Craft::t('emails', 'Subject'),
            'body' => \Craft::t('emails', 'Body'),
            'attachements' => \Craft::t('emails', 'Attachements'),
        ];
    }

    /**
     * Get all defined redactor configuration files
     * 
     * @return array
     */
    public function getRedactorConfigOptions(): array
    {
        $options = ['' => \Craft::t('emails', 'Default')];
        $path = \Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . 'redactor';
        if (is_dir($path)) {
            $files = FileHelper::findFiles($path, [
                'only' => ['*.json'],
                'recursive' => false
            ]);

            foreach ($files as $file) {
                $filename = basename($file);
                if ($filename !== 'Default.json') {
                    $options[$filename] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        ksort($options);
        return $options;
    }
}