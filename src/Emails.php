<?php

namespace Ryssbowh\CraftEmails;

use Craft;
use Ryssbowh\CraftEmails\assets\CraftEmailsAssetBundle;
use Ryssbowh\CraftEmails\behaviors\MessageBehavior;
use Ryssbowh\CraftEmails\emailSources\AllUsersEmailSource;
use Ryssbowh\CraftEmails\emailSources\MailchimpEmailSource;
use Ryssbowh\CraftEmails\emailSources\UserGroupEmailSource;
use Ryssbowh\CraftEmails\events\RegisterEmailSourcesEvent;
use Ryssbowh\CraftEmails\models\Settings;
use Ryssbowh\CraftEmails\models\actions\SendEmail;
use Ryssbowh\CraftEmails\services\AttachementsService;
use Ryssbowh\CraftEmails\services\EmailShotsService;
use Ryssbowh\CraftEmails\services\EmailSourceService;
use Ryssbowh\CraftEmails\services\EmailerService;
use Ryssbowh\CraftEmails\services\EmailsService;
use Ryssbowh\CraftEmails\services\MailchimpService;
use Ryssbowh\CraftEmails\services\MessagesService;
use Ryssbowh\CraftEmails\variables\EmailsVariable;
use Ryssbowh\CraftTriggers\controllers\CpTriggersController;
use Ryssbowh\CraftTriggers\events\RegisterActionsEvent;
use Ryssbowh\CraftTriggers\services\TriggersService;
use craft\base\Plugin;
use craft\db\Table;
use craft\events\DefineBehaviorsEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\App;
use craft\mail\Mailer;
use craft\models\SystemMessage;
use craft\records\Site;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\utilities\ClearCaches;
use craft\utilities\SystemMessages as SystemMessagesUtility;
use craft\web\UrlManager;
use craft\web\View;
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
    public $schemaVersion = '1.3.0';

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
            'emailShots' => EmailShotsService::class,
            'mailchimp' => MailchimpService::class,
            'attachements' => AttachementsService::class,
            'messages' => MessagesService::class
        ]);

        $this->registerMailer();
        $this->registerProjectConfig();
        $this->registerSystemMessages();
        $this->disableSystemMessages();
        $this->registerTwigVariables();
        $this->registerPermissions();
        $this->registerEmailSources();
        $this->registerClearCacheEvent();
        $this->registerSiteTemplates();
        $this->registerSiteChange();
        $this->registerBehaviors();
        $this->registerTriggers();

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
            $e->sender->set('emails', EmailsVariable::class);
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
                        'label' => \Craft::t('emails', 'Emails'),
                    ],
                    'shots' => [
                        'url' => 'emails/shots',
                        'label' => \Craft::t('emails', 'Email shots'),
                    ]
                ];
            }
            return $item;
        }
        return null;
    }

    /**
     * Register behaviors
     */
    protected function registerBehaviors()
    {
        Event::on(
            SystemMessage::class,
            SystemMessage::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->behaviors['messageBehavior'] = ['class' => MessageBehavior::class];
            });
    }

    /**
     * Listen to site after save event, in case their language change
     */
    protected function registerSiteChange()
    {
        Event::on(
            Site::class,
            Site::EVENT_BEFORE_UPDATE,
            function (Event $e) {
                if (!$e->sender->primary) {
                    return;
                }
                $oldLanguage = $e->sender->getOldAttribute('language');
                $newLanguage = $e->sender->language;
                if ($oldLanguage !== $newLanguage) {
                    Emails::$plugin->messages->updatePrimaryMessageLanguage($oldLanguage, $newLanguage);
                }
            }
        );
    }

    /**
     * Integration to triggers plugin
     * @see https://github.com/ryssbowh/craft-triggers/
     */
    protected function registerTriggers()
    {
        if (\Craft::$app->plugins->isPluginInstalled('triggers')) {
            Event::on(TriggersService::class, TriggersService::EVENT_REGISTER_ACTIONS, function (RegisterActionsEvent $e) {
                $e->add(new SendEmail);
            });
            Event::on(CpTriggersController::class, CpTriggersController::EVENT_EDIT_TRIGGER, function (Event $e) {
                \Craft::$app->view->registerAssetBundle(CraftEmailsAssetBundle::class);
            });
        }
    }

    /**
     * Replace Craft mailer
     */
    protected function registerMailer()
    {
        $config = App::mailerConfig();
        $config['class'] = EmailerService::class;
        \Craft::$app->setComponents([
            'mailer' => \Craft::createObject($config)
        ]);
    }

    /**
     * Registers front templates
     */
    protected function registerSiteTemplates()
    {
        Event::on(
            View::class, 
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots[''][] = __DIR__ . '/templates/site';
            }
        );
    }

    /**
     * Registers Clear cache options
     */
    protected function registerClearCacheEvent()
    {
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function (RegisterCacheOptionsEvent $event) {
            $event->options[] = [
                'key' => 'mailchimp_lists',
                'label' => Craft::t('emails', 'Mailchimp lists'),
                'action' => function () {
                    Emails::$plugin->mailchimp->clearCaches();
                }
            ];
        });
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
                foreach (Emails::$plugin->mailchimp->lists as $list) {
                    $e->add(new MailchimpEmailSource([
                        'id' => $list['id']
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
                        'label' => \Craft::t('emails', 'Add and delete emails')
                    ],
                    'modifyEmailContent' => [
                        'label' => \Craft::t('emails', 'Modify emails content')
                    ],
                    'modifyEmailConfig' => [
                        'label' => \Craft::t('emails', 'Modify emails config')
                    ],
                    'seeEmailLogs' => [
                        'label' => \Craft::t('emails', 'See logs')
                    ],
                    'deleteEmailLogs' => [
                        'label' => \Craft::t('emails', 'Delete logs')
                    ],
                    'sendEmails' => [
                        'label' => \Craft::t('emails', 'Send emails')
                    ],
                    'manageEmailShots' => [
                        'label' => \Craft::t('emails', 'Manage email shots')
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
            $event->messages = Emails::$plugin->messages->getAllSystemMessages($event->messages);
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
                'emails/preview/<id:\d+>/<langId>' => 'emails/cp-emails/preview',
                'emails/shots' => 'emails/cp-shots',
                'emails/shots/add' => 'emails/cp-shots/add-shot',
                'emails/shots/edit/<id:\d>' => 'emails/cp-shots/edit-shot',
                'emails/shots/logs/<id:\d>' => 'emails/cp-shots/logs',
                'emails/quick-shot' => 'emails/cp-shots/quick-shot',
                'emails/edit/<id:\d+>' => 'emails/cp-emails/edit-content',
                'emails/edit/<id:\d+>/<langId>' => 'emails/cp-emails/edit-content',
                'emails/logs/<emailId:\d+>' => 'emails/cp-emails/logs',
            ]);
            if (\Craft::$app->config->getGeneral()->allowAdminChanges) {
                $event->rules = array_merge($event->rules, [
                    'emails/add' => 'emails/cp-emails/add',
                    'emails/config/<id:\d+>' => 'emails/cp-emails/edit-config'
                ]);
            }
        });
    }

    /**
     * After theme is installed, creates system emails.
     */
    protected function afterInstall()
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function () {
                Emails::$plugin->emails->install();
            }
        );
    }

    /**
     * Remove all config after uninstall
     */
    protected function afterUninstall()
    {
        Craft::$app->getProjectConfig()->remove('emails');
        \Craft::$app->getDb()->createCommand()
            ->delete(Table::SYSTEMMESSAGES)
            ->execute();
    }
}
