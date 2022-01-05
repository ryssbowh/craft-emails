<?php 

namespace Ryssbowh\CraftEmails\controllers;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\Email;
use Ryssbowh\CraftThemes\assets\DisplayAssets;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;

class CpEmailsController extends Controller
{
    /**
     * All actions require permission 'accessPlugin-emails'
     */
    public function beforeAction($action)
    {
        $this->requirePermission('accessPlugin-emails');
        return true;
    }

    public function actionIndex()
    {
        $allow = \Craft::$app->config->general->allowAdminChanges;
        return $this->renderTemplate('emails/dashboard', [
            'title' => \Craft::t('emails', 'Emails'),
            'emails' => Emails::$plugin->emails->all(),
            'canAddDelete' => $allow && \Craft::$app->getUser()->checkPermission('addDeleteEmailTemplates'),
            'canEditConfig' => $allow && \Craft::$app->getUser()->checkPermission('modifyEmailConfig'),
            'canViewLogs' => \Craft::$app->getUser()->checkPermission('seeEmailLogs')
        ]);
    }

    public function actionAdd()
    {
        $this->requirePermission('addDeleteEmailTemplates');
        return $this->renderTemplate('emails/add', [
            'email' => new Email,
            'settings' => Emails::$plugin->settings
        ]);
    }

    public function actionEditContent(int $id)
    {
        $this->requirePermission('modifyEmailContent');
        return $this->renderTemplate('emails/edit-content', [
            'email' => Emails::$plugin->emails->getById($id),
            'settings' => Emails::$plugin->settings
        ]);
    }

    public function actionEditConfig(int $id)
    {
        $this->requirePermission('modifyEmailConfig');
        return $this->renderTemplate('emails/edit-config', [
            'email' => Emails::$plugin->emails->getById($id),
            'settings' => Emails::$plugin->settings
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->requirePermission('addDeleteEmailTemplates');
        $email = Emails::$plugin->emails->getById($id);
        Emails::$plugin->emails->delete($email);
        return $this->asJson([
            'message' => \Craft::t('emails', 'Email deleted'),
        ]);
    }

    public function actionSaveConfig()
    {
        $this->requirePostRequest();
        $new = true;
        $email = new Email;
        if ($id = $this->request->getBodyParam('id')) {
            $new = false;
            $email = Emails::$plugin->emails->getById($id);
        }
        $email->populateFromPost();
        
        if (Emails::$plugin->emails->save($email)) {
            \Craft::$app->session->setNotice(\Craft::t('emails', 'Email saved.'));
            return $this->redirect(UrlHelper::cpUrl('emails'));
        }
        $template = $new ? 'emails/add' : 'emails/edit-config';
        return $this->renderTemplate($template, [
            'email' => $email,
            'settings' => Emails::$plugin->settings
        ]);
    }

    public function actionSaveContent()
    {
        $this->requirePostRequest();
        $id = $this->request->getRequiredBodyParam('id');
        $email = Emails::$plugin->emails->getById($id);
        $record = Emails::$plugin->emails->getRecordById($id);
        $configDriven = Emails::$plugin->settings->configDriven;
        foreach ($email->safeAttributes() as $attribute) {
            if (!in_array($attribute, $configDriven) and $this->request->getBodyParam($attribute) !== null) {
                $record->$attribute = $this->request->getBodyParam($attribute);
            }
        }
        $record->save(false);
        \Craft::$app->session->setNotice(\Craft::t('emails', 'Email saved.'));
        return $this->redirect(UrlHelper::cpUrl('emails'));
    }

    public function actionDeleteLogs(int $emailId)
    {
        $this->requirePermission('deleteEmailLogs');
        $email = Emails::$plugin->emails->getById($emailId);
        Emails::$plugin->emails->deleteLogs($email);
        \Craft::$app->session->setNotice(\Craft::t('emails', 'All logs deleted'));
        return $this->redirect(UrlHelper::cpUrl('emails/logs/' . $email->id));
    }

    public function actionLogs(int $emailId)
    {
        $this->requirePermission('seeEmailLogs');
        $email = Emails::$plugin->emails->getById($emailId);
        list($models, $pages) = Emails::$plugin->emails->getLogs($email);
        return $this->renderTemplate('emails/logs', [
            'email' => $email,
            'logs' => $models,
            'pages' => $pages
        ]);
    }
}