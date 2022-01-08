<?php 

namespace Ryssbowh\CraftEmails\controllers;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\Email;
use Ryssbowh\CraftEmails\Models\EmailShot;
use Ryssbowh\CraftEmails\assets\EmailsAssetBundle;
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

    /**
     * Email dashboard action
     * 
     * @return Response
     */
    public function actionIndex()
    {
        \Craft::$app->view->registerAssetBundle(EmailsAssetBundle::class);
        $allow = \Craft::$app->config->general->allowAdminChanges;
        return $this->renderTemplate('emails/emails', [
            'title' => \Craft::t('emails', 'Emails'),
            'emails' => Emails::$plugin->emails->all(),
            'canAddDelete' => $allow && \Craft::$app->getUser()->checkPermission('addDeleteEmailTemplates'),
            'canEditConfig' => $allow && \Craft::$app->getUser()->checkPermission('modifyEmailConfig'),
            'canViewLogs' => \Craft::$app->getUser()->checkPermission('seeEmailLogs')
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
        \Craft::$app->view->registerAssetBundle(EmailsAssetBundle::class);
        return $this->renderTemplate('emails/add-email', [
            'email' => new Email,
            'settings' => Emails::$plugin->settings
        ]);
    }

    /**
     * Edit email content action
     *
     * @param  int $id
     * @return Response
     */
    public function actionEditContent(int $id)
    {
        $this->requirePermission('modifyEmailContent');
        \Craft::$app->view->registerAssetBundle(EmailsAssetBundle::class);
        return $this->renderTemplate('emails/edit-content', [
            'email' => Emails::$plugin->emails->getById($id),
            'settings' => Emails::$plugin->settings
        ]);
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
        \Craft::$app->view->registerAssetBundle(EmailsAssetBundle::class);
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

    /**
     * Save email content action
     * 
     * @return Response
     */
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
        \Craft::$app->view->registerAssetBundle(EmailsAssetBundle::class);
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
}