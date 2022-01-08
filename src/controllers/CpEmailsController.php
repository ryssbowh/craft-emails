<?php 

namespace Ryssbowh\CraftEmails\controllers;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\Email;
use Ryssbowh\CraftEmails\Models\EmailShot;
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
            if ($new) {
                return $this->redirect(UrlHelper::cpUrl('emails/edit/' . $email->id));    
            }
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

    public function actionLogs(int $emailId)
    {
        $this->requirePermission('seeEmailLogs');
        $email = Emails::$plugin->emails->getById($emailId);
        $orderSide = $this->request->getParam('orderSide', 'desc');
        $order = $this->request->getParam('order', 'dateCreated');
        list($models, $pages) = Emails::$plugin->emails->getLogs($email, $order, $orderSide);
        return $this->renderTemplate('emails/logs', [
            'email' => $email,
            'logs' => $models,
            'pages' => $pages
        ]);
    }
}