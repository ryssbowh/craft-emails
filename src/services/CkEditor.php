<?php

namespace Ryssbowh\CraftEmails\services;

use craft\base\Component;
use craft\ckeditor\helpers\CkeditorConfig;
use craft\ckeditor\helpers\CkeditorConfigSchema;
use craft\ckeditor\web\assets\fieldsettings\FieldSettingsAsset;

/**
 * @since 4.0.0
 */
class CkEditor extends Component
{
    /**
     * Check that the installed version of ckeditor is at least 5
     *
     * @return bool
     */
    public function isVersionAtLeast5(): bool
    {
        if (!\Craft::$app->plugins->isPluginInstalled('ckeditor')) {
            return false;
        }
        $ckeditorVersion = \Craft::$app->plugins->getPlugin('ckeditor')->getVersion();
        return version_compare($ckeditorVersion, '5.0.0', '>=');
    }

    /**
     * Get variables necessary to edit an email config
     *
     * @return array
     */
    public function configVariables(): array
    {
        $view = \Craft::$app->getView();
        $bundle = $view->registerAssetBundle(FieldSettingsAsset::class);
        $jsonSchemaUri = sprintf('https://craft-code-editor.com/%s', $view->namespaceInputId('config-options-json'));
        $volumeOptions = [];
        foreach (\Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            if ($volume->getFs()->hasUrls) {
                $volumeOptions[] = [
                    'label' => $volume->name,
                    'value' => $volume->uid,
                ];
            }
        }
        $transformOptions = [];
        foreach (\Craft::$app->getImageTransforms()->getAllTransforms() as $transform) {
            $transformOptions[] = [
                'label' => $transform->name,
                'value' => $transform->uid,
            ];
        }
        return [
            'toolbarItems' => CkeditorConfig::normalizeToolbarItems(CkeditorConfig::$toolbarItems),
            'importStatements' => CkeditorConfig::getImportStatements(),
            'toolbarBuilderId' => $view->namespaceInputId('ckeConfigJson-toolbar-builder'),
            'configOptionsId' => $view->namespaceInputId('ckeConfigJson-config-options'),
            'jsonSchemaUri' => $jsonSchemaUri,
            'jsonSchema' => CkeditorConfigSchema::create(),
            'plugins' => CkeditorConfig::getAllPlugins(),
            'advanceLinkOptions' => CkeditorConfig::advanceLinkOptions(),
            'volumeOptions' => $volumeOptions,
            'baseIconsUrl' => "$bundle->baseUrl/images",
            'transformOptions' => $transformOptions,
            'defaultTransformOptions' => array_merge(
                [
                    [
                        'label' => \Craft::t('ckeditor', 'No transform'),
                        'value' => null,
                    ],
                ],
                $transformOptions,
            ),
        ];
    }

    /**
     * The base config when creating new emails
     *
     * @return array
     */
    public function baseConfig(): array
    {
        return [
            'toolbar' => ['heading', 'bold', 'italic', 'underline', 'strikethrough'],
            'js' => 'return {\r\n  \r\n}',
            'json' => '',
            'showWordCount' => '',
            'headingLevels' => ['1', '2', '3', '4', '5', '6'],
            'advancedLinkFields' => '',
        ];
    }
}
