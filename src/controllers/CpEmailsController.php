<?php 

namespace Ryssbowh\CraftEmails\controllers;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\helpers\RedactorHelper;
use Ryssbowh\CraftEmails\models\Email;
use Ryssbowh\CraftEmails\models\EmailShot;
use Ryssbowh\CraftThemes\assets\DisplayAssets;
use craft\helpers\App;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\mail\Mailer;
use craft\models\SystemMessage;
use craft\web\Controller;
use yii\base\Event;
use yii\helpers\Markdown;
use yii\mail\MailEvent;
use yii\web\ForbiddenHttpException;

class CpEmailsController extends Controller
{
    /**
     * All actions require permission 'accessPlugin-emails'
     */
    public function beforeAction($action): bool
    {
        $this->requirePermission('accessPlugin-emails');
        return true;
    }

    /**
     * Email dashboard action
     * 
     * @return Response
     */
    public function actionIndex()
    {
        $allow = \Craft::$app->config->general->allowAdminChanges;
        return $this->renderTemplate('emails/emails', [
            'title' => \Craft::t('emails', 'Emails'),
            'emails' => Emails::$plugin->emails->all,
            'canAddDelete' => $allow && \Craft::$app->getUser()->checkPermission('addDeleteEmailTemplates'),
            'canEditConfig' => $allow && \Craft::$app->getUser()->checkPermission('modifyEmailConfig'),
            'canViewLogs' => \Craft::$app->getUser()->checkPermission('seeEmailLogs')
        ]);
    }

    /**
     * Preview action
     * 
     * @param  int    $id
     * @param  string $langId
     * @return Reponse
     */
    public function actionPreview(int $id, string $langId)
    {
        $this->requirePermission('modifyEmailContent');
        $email = Emails::$plugin->emails->getById($id);
        $view = \Craft::$app->view;
        $generalConfig = \Craft::$app->getConfig()->getGeneral();
        // Use the posted language
        $language = \Craft::$app->language;
        \Craft::$app->language = $langId;
        $message = \Craft::$app->systemMessages->getMessage($email->key, $langId);
        Event::trigger(Mailer::class, Mailer::EVENT_BEFORE_PREP, new MailEvent([
            'message' => $message,
        ]));
        // Temporarily disable lazy transform generation
        $generateTransformsBeforePageLoad = $generalConfig->generateTransformsBeforePageLoad;
        $generalConfig->generateTransformsBeforePageLoad = true;
        $settings = App::mailSettings();
        $fromEmail = $email->from ? \Craft::parseEnv($email->from) : \Craft::parseEnv($settings->fromEmail);
        $fromName = $email->fromName ? \Craft::parseEnv($email->fromName) : \Craft::parseEnv($settings->fromName);
        $replyToEmail = $email->replyTo ? \Craft::parseEnv($email->replyTo) : \Craft::parseEnv($settings->replyToEmail);
        $variables = $message->variables ?? [] + [
            'emailKey' => $email->key,
            'fromEmail' => $fromEmail,
            'replyToEmail' => $replyToEmail,
            'fromName' => $fromName,
        ];
        $subject = $this->request->getParam('subject');
        $body = $this->request->getParam('body');
        $subjectError = '';
        $bodyError = '';
        try {
            $subject = $view->renderString($subject, $variables, $view::TEMPLATE_MODE_SITE);
        } catch (\Throwable $e) {
            $subjectError = \Craft::t('emails', 'Error while rendering subject, raw twig is displayed.');
        }
        try {
            $body = $view->renderString($body, $variables, $view::TEMPLATE_MODE_SITE);
        } catch (\Throwable $e) {
            $bodyError = \Craft::t('emails', 'Error while rendering body, raw twig is displayed.');
        }
        $body = $view->renderTemplate($email->template, array_merge($variables, [
            'body' => Template::raw(Markdown::process($body)),
        ]), $view::TEMPLATE_MODE_SITE);
        // Set things back to normal
        \Craft::$app->language = $language;
        $generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;
        return $this->asJson([
            'subject' => $subject,
            'body' => $body,
            'subjectError' => $subjectError,
            'bodyError' => $bodyError
        ]);
    }

    /**
     * Add email action
     * 
     * @return Response
     */
    public function actionAdd()
    {
        $this->requirePermission('addDeleteEmailTemplates');
        return $this->renderTemplate('emails/add-email', [
            'email' => new Email,
            'settings' => Emails::$plugin->settings
        ]);
    }

    /**
     * Action add translation
     * 
     * @return Response
     */
    public function actionAddTranslation()
    {
        $this->requirePermission('modifyEmailContent');
        $key = $this->request->getRequiredParam('key');
        $langId = $this->request->getRequiredParam('localeId');
        $locale = \Craft::$app->i18n->getLocaleById($langId);
        if (Emails::$plugin->messages->addTranslation($key, $langId)) {
            \Craft::$app->session->setNotice(\Craft::t('emails', 'Translation for {lang} added.', ['lang' => $locale->displayName]));
            return true;    
        }
        $this->response->setStatusCode(400);
        return $this->asJson([
            'message' => \Craft::t('emails', "Couldn't add translation")
        ]);
    }

    /**
     * Action delete translation
     * 
     * @return Response
     */
    public function actionDeleteTranslation()
    {
        $this->requirePermission('modifyEmailContent');
        $key = $this->request->getRequiredParam('key');
        $langId = $this->request->getRequiredParam('localeId');
        $locale = \Craft::$app->i18n->getLocaleById($langId);
        if (Emails::$plugin->messages->deleteTranslation($key, $langId)) {
            \Craft::$app->session->setNotice(\Craft::t('emails', 'Translation for {lang} deleted.', ['lang' => $locale->displayName]));
            return true;    
        }
        $this->response->setStatusCode(400);
        return $this->asJson([
            'message' => \Craft::t('emails', "Couldn't delete translation")
        ]);
    }

    /**
     * Edit email content action
     *
     * @param  int $id
     * @return Response
     */
    public function actionEditContent(int $id, ?string $langId = null)
    {
        if ($langId === null) {
            $langId = \Craft::$app->getSites()->getPrimarySite()->language;
        }
        $email = Emails::$plugin->emails->getById($id);
        $message = $email->getMessage($langId);
        return $this->editContent($message, $langId);
    }

    /**
     * Save email content action
     * 
     * @return Response
     */
    public function actionSaveContent()
    {
        $this->requirePermission('modifyEmailContent');
        $langId = $this->request->getRequiredParam('langId');
        $attachements = $this->request->getParam('attachements', []);
        $key = $this->request->getRequiredParam('key');
        $email = Emails::$plugin->emails->getByKey($key);
        if (is_string($attachements)) {
            $attachements = [];
        }
        $body = $this->request->getRequiredParam('body');
        $message = new SystemMessage([
            'key' => $this->request->getRequiredParam('key'),
            'subject' => $this->request->getRequiredParam('subject'),
            'body' => $email->plain ? $body : RedactorHelper::serializeBody($body)
        ]);
        if (Emails::$plugin->messages->saveMessage($message, $langId, $attachements)) {
            \Craft::$app->session->setNotice(\Craft::t('emails', 'Content saved'));
            return $this->redirect(UrlHelper::cpUrl('emails'));
        }
        return $this->editContent($message, $langId);
    }

    /**
     * Edit email config action
     *
     * @param  int $id
     * @return Response
     */
    public function actionEditConfig(int $id)
    {
        $this->requirePermission('modifyEmailConfig');
        return $this->renderTemplate('emails/edit-config', [
            'email' => Emails::$plugin->emails->getById($id),
            'settings' => Emails::$plugin->settings
        ]);
    }

    /**
     * Delete email action
     *
     * @param  int $id
     * @return Response
     */
    public function actionDelete(int $id)
    {
        $this->requirePermission('addDeleteEmailTemplates');
        $email = Emails::$plugin->emails->getById($id);
        if (Emails::$plugin->emails->delete($email)) {
            $message = \Craft::t('emails', 'Email has been deleted.');
            if ($this->request->isAjax) {
                return $this->asJson([
                    'message' => $message
                ]);
            }
            \Craft::$app->session->setNotice($message);
            return $this->redirect(UrlHelper::cpUrl('emails/list'));
        }
        $message = \Craft::t('emails', 'Error while deleting email.');
        if ($this->request->isAjax) {
            $this->response->setStatusCode(400);
            return $this->asJson([
                'message' => $message
            ]);
        }
        \Craft::$app->session->setNotice($message);
        return $this->redirect(UrlHelper::cpUrl('emails/list'));
    }

    /**
     * Save config action
     * 
     * @return Response
     */
    public function actionSaveConfig()
    {
        $this->requirePermission('modifyEmailConfig');
        $new = true;
        $email = new Email;
        if ($id = $this->request->getParam('id')) {
            $new = false;
            $email = Emails::$plugin->emails->getById($id);
        }
        $email->populateFromPost();
        
        if (Emails::$plugin->emails->save($email)) {
            \Craft::$app->session->setNotice(\Craft::t('emails', 'Email saved.'));
            if ($new) {
                return $this->redirect(UrlHelper::cpUrl('emails/edit/' . $email->id));    
            }
            return $this->redirect(UrlHelper::cpUrl('emails'));
        }
        $template = $new ? 'emails/add-email' : 'emails/edit-config';
        return $this->renderTemplate($template, [
            'email' => $email,
            'settings' => Emails::$plugin->settings
        ]);
    }

    /**
     * Delete email logs action
     * 
     * @return Response
     */
    public function actionDeleteLogs()
    {
        $this->requirePermission('deleteEmailLogs');
        $id = $this->request->getRequiredParam('id');
        $email = Emails::$plugin->emails->getById($id);
        $ids = $this->request->getParam('ids');
        Emails::$plugin->emails->deleteLogs($email, $ids);
        \Craft::$app->session->setNotice(\Craft::t('emails', 'Logs have been deleted.'));
        return true;
    }

    /**
     * View email logs action
     * 
     * @return Response
     */
    public function actionLogs(int $emailId)
    {
        $this->requirePermission('seeEmailLogs');
        $email = Emails::$plugin->emails->getById($emailId);
        $orderSide = $this->request->getParam('orderSide', 'desc');
        $order = $this->request->getParam('order', 'dateCreated');
        list($models, $pages) = Emails::$plugin->emails->getLogs($email, $order, $orderSide);
        return $this->renderTemplate('emails/email-logs', [
            'email' => $email,
            'logs' => $models,
            'pages' => $pages
        ]);
    }

    /**
     * View email action
     * 
     * @return Response
     */
    public function actionView()
    {
        $this->requirePermission('seeEmailLogs');
        $id = $this->request->getRequiredParam('id');
        $log = Emails::$plugin->emails->getLogById($id);
        return $this->asJson($log->toArray());
    }

    /**
     * Resend email action
     * 
     * @return Response
     */
    public function actionResend()
    {
        $this->requirePermission('sendEmails');
        $id = $this->request->getRequiredParam('id');
        $log = Emails::$plugin->emails->getLogById($id);
        if (Emails::$plugin->emails->resend($log)) {
            $message = \Craft::t('emails', 'Email has been resent.');
        } else {
            $this->response->setStatusCode(400);
            $message = \Craft::t('emails', 'Error while resending the email.');
        }
        return $this->asJson([
            'message' => $message
        ]);
    }

    /**
     * Edit system message
     * 
     * @param  SystemMessage $message
     * @param  string        $langId
     * @return Response
     */
    protected function editContent(SystemMessage $message, string $langId)
    {
        $email = Emails::$plugin->emails->getByKey($message->key);
        $emailLocales = $email->allDefinedLanguages;
        $translatableLocales = [];
        foreach (\Craft::$app->i18n->getSiteLocales() as $locale) {
            if (!isset($emailLocales[$locale->id])) {
                $translatableLocales[$locale->id] = $locale->displayName;
            }
        }
        asort($translatableLocales);
        return $this->renderTemplate('emails/edit-content', [
            'email' => $email,
            'message' => $message,
            'langId' => $langId,
            'locale' => \Craft::$app->i18n->getLocaleById($langId),
            'emailLocales' => $emailLocales,
            'translatableLocales' => $translatableLocales,
            'settings' => Emails::$plugin->settings,
            'primaryLanguage' => \Craft::$app->getSites()->getPrimarySite()->language,
            'attachements' => Emails::$plugin->attachements->get($email->key, $langId, true)
        ]);
    }
}