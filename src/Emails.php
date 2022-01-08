<?php

namespace Ryssbowh\CraftEmails;

use Craft;
use Ryssbowh\CraftEmails\Events\RegisterEmailSourcesEvent;
use Ryssbowh\CraftEmails\Models\Settings;
use Ryssbowh\CraftEmails\Services\EmailShotsService;
use Ryssbowh\CraftEmails\Services\EmailSourceService;
use Ryssbowh\CraftEmails\Services\EmailsService;
use Ryssbowh\CraftEmails\emailSources\AllUsersEmailSource;
use Ryssbowh\CraftEmails\emailSources\UserGroupEmailSource;
use craft\base\Plugin;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\mail\Mailer;
use craft\services\ProjectConfig;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\utilities\SystemMessages as SystemMessagesUtility;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use yii\mail\BaseMailer;

class Emails extends Plugin
{
    /**
     * @var Emails
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'emails' => EmailsService::class,
            'emailSources' => EmailSourceService::class,
            'emailShots' => EmailShotsService::class
        ]);

        $this->registerProjectConfig();
        $this->registerSystemMessages();
        $this->disableSystemMessages();
        $this->registerEmailEvents();
        $this->registerTwigVariables();
        $this->registerPermissions();
        $this->registerEmailSources();

        if (Craft::$app->request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'Ryssbowh\\CraftEmails\\console';
        }

        if (Craft::$app->request->getIsCpRequest()) {
            $this->registerCpRoutes();
        }
    }

    /**
     * Register new twig variable craft.emails
     */
    public function registerTwigVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
            $e->sender->set('emails', Emails::$plugin->emails);
        });
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem ()
    {
        if (\Craft::$app->user->checkPermission('accessPlugin-emails')) {
            $item = parent::getCpNavItem();
            $item['label'] = $this->settings->menuItemName ?: \Craft::t('emails', 'Emails');
            if (\Craft::$app->user->checkPermission('sendEmails')) {
                $item['subnav'] = [
                    'emails' => [
                        'url' => 'emails/list',
                        'label' => \Craft::t('themes', 'Emails'),
                    ],
                    'shots' => [
                        'url' => 'emails/shots',
                        'label' => \Craft::t('themes', 'Email shots'),
                    ]
                ];
            }
            return $item;
        }
        return null;
    }

    /**
     * Register default email sources
     */
    protected function registerEmailSources()
    {
        Event::on(
            EmailSourceService::class,
            EmailSourceService::EVENT_REGISTER,
            function (RegisterEmailSourcesEvent $e) {
                $e->add(new AllUsersEmailSource);
                foreach (\Craft::$app->userGroups->getAllGroups() as $group) {
                    $e->add(new UserGroupEmailSource([
                        'group' => $group
                    ]));
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritDoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'emails/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    /**
     * Events before and after an email is sent
     */
    protected function registerEmailEvents()
    {
        Event::on(Mailer::class, Mailer::EVENT_BEFORE_PREP, function (Event $event) {
            Emails::$plugin->emails->modifyMessage($event->message);
        });
        Event::on(BaseMailer::class, BaseMailer::EVENT_AFTER_SEND, function ($event) {
            Emails::$plugin->emails->afterSent($event->message, $event->isSuccessful);
        });
    }

    /**
     * Disable Craft system messages
     */
    protected function disableSystemMessages()
    {
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                foreach ($event->types as $index => $type) {
                    if ($type == SystemMessagesUtility::class) {
                        unset($event->types[$index]);
                    }
                }
            }
        );
    }

    /**
     * Registers permissions
     */
    protected function registerPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[\Craft::t('emails', 'Emails')] = [
                    'addDeleteEmailTemplates' => [
                        'label' => \Craft::t('emails', 'Add and delete email templates')
                    ],
                    'modifyEmailContent' => [
                        'label' => \Craft::t('emails', 'Modify emails content')
                    ],
                    'modifyEmailConfig' => [
                        'label' => \Craft::t('emails', 'Modify emails config')
                    ],
                    'seeEmailLogs' => [
                        'label' => \Craft::t('emails', 'See emails logs')
                    ],
                    'deleteEmailLogs' => [
                        'label' => \Craft::t('emails', 'Delete emails logs')
                    ],
                    'manageEmailShots' => [
                        'label' => \Craft::t('emails', 'Manage and send email shots')
                    ]
                ];
            }
        );
    }

    /**
     * Register our own system messages
     */
    protected function registerSystemMessages()
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
            $event->messages = Emails::$plugin->emails->replaceSystemMessages($event->messages);
        });
    }

    /**
     * Registers project config events
     */
    protected function registerProjectConfig()
    {
        Craft::$app->projectConfig
            ->onAdd(EmailsService::CONFIG_KEY.'.{uid}',      [$this->emails, 'handleChanged'])
            ->onUpdate(EmailsService::CONFIG_KEY.'.{uid}',   [$this->emails, 'handleChanged'])
            ->onRemove(EmailsService::CONFIG_KEY.'.{uid}',   [$this->emails, 'handleDeleted']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $e) {
            Emails::$plugin->emails->rebuildConfig($e);
        });
    }

    /**
     * Register cp routes
     */
    protected function registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'emails' => 'emails/cp-emails',
                'emails/list' => 'emails/cp-emails',
                'emails/shots' => 'emails/cp-shots',
                'emails/shots/add' => 'emails/cp-shots/add-shot',
                'emails/shots/edit/<id:\d>' => 'emails/cp-shots/edit-shot',
                'emails/shots/logs/<id:\d>' => 'emails/cp-shots/logs',
                'emails/quick-shot' => 'emails/cp-shots/quick-shot',
                'emails/edit/<id:\d+>' => 'emails/cp-emails/edit-content',
                'emails/logs/<emailId:\d+>' => 'emails/cp-emails/logs'
            ]);
            if (\Craft::$app->config->getGeneral()->allowAdminChanges) {
                $event->rules = array_merge($event->rules, [
                    'emails/add' => 'emails/cp-emails/add',
                    'emails/config/<id:\d+>' => 'emails/cp-emails/edit-config',
                ]);
            }
        });
    }

    /**
     * Install Redactor plugin before installing
     * 
     * @return bool
     */
    protected function beforeInstall(): bool
    {
        \Craft::$app->plugins->installPlugin('redactor');
        return true;
    }

    /**
     * After theme is installed, creates system emails.
     */
    protected function afterInstall()
    {
        Emails::$plugin->emails->install();
    }

    /**
     * Remove all config after uninstall
     */
    protected function afterUninstall()
    {
        Craft::$app->getProjectConfig()->remove('emails');
    }
}
