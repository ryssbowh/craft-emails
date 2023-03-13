<?php

namespace Ryssbowh\CraftEmails\helpers;

use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\models\Section;
use craft\redactor\Field as RedactorField;
use craft\redactor\assets\redactor\RedactorAsset;
use craft\redactor\events\ModifyRedactorConfigEvent;
use craft\validators\HandleValidator;
use yii\base\Event;

class RedactorHelper
{
    /**
     * Get redactor settings
     *
     * @param ?string $redactorConfigFile
     * @return array
     */
    public static function getRedactorSettings(?string $redactorConfigFile): array
    {
        $redactorConfig = [];
        if ($redactorConfigFile) {
            $file = \Craft::getAlias('@config/redactor/' . $redactorConfigFile);
            if (file_exists($file)) {
                $redactorConfig = json_decode(file_get_contents($file), true);
            }
        }
        // Give plugins a chance to modify the Redactor config
        $event = new ModifyRedactorConfigEvent([
            'config' => $redactorConfig
        ]);
        Event::trigger(RedactorField::class, RedactorField::EVENT_DEFINE_REDACTOR_CONFIG, $event);
        $redactorConfig = $event->config;

        if (isset($redactorConfig['plugins'])) {
            foreach ($redactorConfig['plugins'] as $plugin) {
                RedactorField::registerRedactorPlugin($plugin);
            }
        }
        $bundle = \Craft::$app->view->getAssetManager()->getBundle(RedactorAsset::class);
        $redactorLang = $bundle::$redactorLanguage ?? 'en';
        $site = \Craft::$app->getSites()->getCurrentSite();
        $locale = \Craft::$app->getI18n()->getLocaleById($site->language);
        return [
            'id' => 'field-body',
            'linkOptions' => static::_getLinkOptions(),
            'volumes' => static::_getVolumeKeys(),
            'transforms' => static::_getTransforms(),
            'defaultTransform' => '',
            'elementSiteId' => $site->id,
            'redactorConfig' => $redactorConfig,
            'redactorLang' => $redactorLang,
            'direction' => $locale->getOrientation()
        ];
    }

    /**
     * Find any element URLs and swap them with ref tags
     *
     * @param  string $body
     * @return string
     */
    public static function serializeBody(string $body): string
    {
        return preg_replace_callback(
            '/(href=|src=)([\'"])([^\'"\?#]*)(\?[^\'"\?#]+)?(#[^\'"\?#]+)?(?:#|%23)([\w\\\\]+)\:(\d+)(?:@(\d+))?(\:(?:transform\:)?' . HandleValidator::$handlePattern . ')?\2/',
            function ($matches) {
                list(, $attr, $q, $url, $query, $hash, $elementType, $ref, $siteId, $transform) = array_pad($matches, 10, null);

                // Create the ref tag, and make sure :url is in there
                $ref = $elementType . ':' . $ref . ($siteId ? "@$siteId" : '') . ($transform ?: ':url');

                if ($query || $hash) {
                    // Make sure that the query/hash isn't actually part of the parsed URL
                    // - someone's Entry URL Format could include "?slug={slug}" or "#{slug}", etc.
                    // - assets could include ?mtime=X&focal=none, etc.
                    $parsed = Craft::$app->getElements()->parseRefs("{{$ref}}");
                    if ($query) {
                        // Decode any HTML entities, e.g. &amp;
                        $query = Html::decode($query);
                        if (mb_strpos($parsed, $query) !== false) {
                            $url .= $query;
                            $query = '';
                        }
                    }
                    if ($hash && mb_strpos($parsed, $hash) !== false) {
                        $url .= $hash;
                        $hash = '';
                    }
                }
                return $attr . $q . '{' . $ref . '||' . $url . '}' . $query . $hash . $q;
            },
            $body
        );
    }

    /**
     * Get volumes keys
     *
     * @return array
     */
    protected static function _getVolumeKeys(): array
    {
        $criteria = ['parentId' => ':empty:'];

        $allVolumes = \Craft::$app->getVolumes()->getAllVolumes();
        $allowedVolumes = [];
        $userService = \Craft::$app->getUser();

        foreach ($allVolumes as $volume) {
            if ($userService->checkPermission("viewAssets:$volume->uid")) {
                $allowedVolumes[] = 'volume:' . $volume->uid;
            }
        }

        return $allowedVolumes;
    }

    /**
     * Get available transforms
     *
     * @return array
     */
    protected static function _getTransforms(): array
    {
        $allTransforms = \Craft::$app->getImageTransforms()->getAllTransforms();
        $transformList = [];

        foreach ($allTransforms as $transform) {
            $transformList[] = [
                'handle' => Html::encode($transform->handle),
                'name' => Html::encode($transform->name)
            ];
        }

        return $transformList;
    }

    /**
     * Link options
     *
     * @return array
     */
    protected static function _getLinkOptions(): array
    {
        $linkOptions = [];

        $sectionSources = static::_getSectionSources();
        $categorySources = static::_getCategorySources();

        if (!empty($sectionSources)) {
            $linkOptions[] = [
                'optionTitle' => \Craft::t('redactor', 'Link to an entry'),
                'elementType' => Entry::class,
                'refHandle' => Entry::refHandle(),
                'sources' => $sectionSources,
                'criteria' => ['uri' => ':notempty:']
            ];
        }

        if (!empty(static::_getVolumeKeys())) {
            $linkOptions[] = [
                'optionTitle' => \Craft::t('redactor', 'Link to an asset'),
                'elementType' => Asset::class,
                'refHandle' => Asset::refHandle(),
                'sources' => static::_getVolumeKeys(),
            ];
        }

        if (!empty($categorySources)) {
            $linkOptions[] = [
                'optionTitle' => \Craft::t('redactor', 'Link to a category'),
                'elementType' => Category::class,
                'refHandle' => Category::refHandle(),
                'sources' => $categorySources,
            ];
        }

        return $linkOptions;
    }

    /**
     * Returns the available category sources.
     *
     * @return array
     */
    private static function _getCategorySources(): array
    {
        $sources = [];
        $categoryGroups = \Craft::$app->getCategories()->getAllGroups();
        $sites = \Craft::$app->getSites()->getAllSites();

        foreach ($categoryGroups as $categoryGroup) {
            $categoryGroupSiteSettings = $categoryGroup->getSiteSettings();
            foreach ($sites as $site) {
                if (isset($categoryGroupSiteSettings[$site->id]) && $categoryGroupSiteSettings[$site->id]->hasUrls) {
                    $sources[] = 'group:' . $categoryGroup->uid;
                    break;
                }
            }
        }

        return $sources;
    }

    /**
     * Returns the available section sources.
     *
     * @return array
     */
    protected static function _getSectionSources(): array
    {
        $sources = [];
        $sections = \Craft::$app->getSections()->getAllSections();
        $showSingles = false;

        // Get all sites
        $sites = \Craft::$app->getSites()->getAllSites();

        foreach ($sections as $section) {
            if ($section->type === Section::TYPE_SINGLE) {
                $showSingles = true;
            } else {
                $sectionSiteSettings = $section->getSiteSettings();
                foreach ($sites as $site) {
                    if (isset($sectionSiteSettings[$site->id]) && $sectionSiteSettings[$site->id]->hasUrls) {
                        $sources[] = 'section:' . $section->uid;
                        break;
                    }
                }
            }
        }

        if ($showSingles) {
            array_unshift($sources, 'singles');
        }

        if (!empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }
}
