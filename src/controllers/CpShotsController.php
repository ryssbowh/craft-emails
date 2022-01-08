<?php 

namespace Ryssbowh\CraftEmails\controllers;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\Email;
use Ryssbowh\CraftEmails\Models\EmailShot;
use Ryssbowh\CraftThemes\assets\DisplayAssets;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;

class CpShotsController extends Controller
{
    /**
     * All actions require permission 'accessPlugin-emails'
     */
    public function beforeAction($action)
    {
        $this->requirePermission('accessPlugin-emails');
        $this->requirePermission('manageEmailShots');
        return true;
    }

    public function actionIndex()
    {
        return $this->renderTemplate('emails/shots', [
            'shots' => Emails::$plugin->emailShots->all()
        ]);
    }

    public function actionAddShot(?EmailShot $shot = null)
    {
        if (!$shot) {
            $shot = new EmailShot;
        }
        return $this->renderTemplate('emails/add-shot', [
            'allEmails' => $this->allEmails(),
            'allSources' => $this->allSources(),
            'errors' => $shot->errors,
            'shot' => $shot
        ]);
    }

    public function actionEditShot(int $id)
    {
        $shot = Emails::$plugin->emailShots->getById($id);
        return $this->actionAddShot($shot);
    }

    public function actionSaveShot()
    {
        if ($id = \Craft::$app->request->getBodyParam('id')) {
            $shot = Emails::$plugin->emailShots->getById($id);
        } else {
            $shot = new EmailShot;
        }
        $shot->scenario = 'create';
        $users = $this->request->getBodyParam('users', []);
        $shot->setAttributes([
            'users' => is_array($users) ? $users : [],
            'emails' => $this->request->getBodyParam('emails', []),
            'sources' => $this->request->getBodyParam('sources', []),
            'email_id' => $this->request->getBodyParam('email_id'),
            'useQueue' => $this->request->getBodyParam('useQueue', true),
            'saveLogs' => $this->request->getBodyParam('saveLogs', false),
            'handle' => $this->request->getBodyParam('handle'),
            'name' => $this->request->getBodyParam('name'),
        ]);
        if (Emails::$plugin->emailShots->save($shot)) {
            \Craft::$app->session->setNotice(\Craft::t('emails', 'Email shot saved.'));
            return $this->redirect(UrlHelper::cpUrl('emails/shots'));
        }
        return $this->actionAddShot($shot);
    }

    public function actionDelete()
    {
        $id = $this->request->getRequiredParam('id');
        $shot = Emails::$plugin->emailShots->getById($id);
        if (Emails::$plugin->emailShots->delete($shot)) {
            $message = \Craft::t('emails', 'Email shot has been deleted.');
            if ($this->request->isAjax) {
                return $this->asJson([
                    'message' => $message
                ]);
            }
            \Craft::$app->session->setNotice($message);
            return $this->redirect(UrlHelper::cpUrl('emails/shots'));
        }
        $message = \Craft::t('emails', 'Error while deleting email shot.');
        if ($this->request->isAjax) {
            $this->response->setStatusCode(400);
            return $this->asJson([
                'message' => $message
            ]);
        }
        \Craft::$app->session->setNotice($message);
        return $this->redirect(UrlHelper::cpUrl('emails/shots'));
    }

    public function actionSend()
    {
        $id = $this->request->getRequiredParam('id');
        $shot = Emails::$plugin->emailShots->getById($id);
        Emails::$plugin->emailShots->send($shot);
        if ($shot->useQueue) {
            $message = \Craft::t('emails', '{number} emails have been sent to the queue.', ['number' => $shot->emailCount]);
        } else {
            $message = \Craft::t('emails', '{number} emails sent.', ['number' => $shot->emailCount]);
        }
        if ($this->request->isAjax) {
            return $this->asJson([
                'message' => $message
            ]);
        }
        \Craft::$app->session->setNotice($message);
        return $this->redirect(UrlHelper::cpUrl('emails/shots'));
    }

    public function actionQuickShot(?EmailShot $shot = null)
    {
        if (!$shot) {
            $shot = new EmailShot;
        }
        return $this->renderTemplate('emails/quick-shot', [
            'allEmails' => $this->allEmails(),
            'allSources' => $this->allSources(),
            'errors' => $shot->errors,
            'shot' => $shot
        ]);
    }

    public function actionSendQuickShot()
    {
        $users = $this->request->getBodyParam('users', []);
        $shot = new EmailShot([
            'sources' => $this->request->getBodyParam('sources', []),
            'users' => is_array($users) ? $users : [],
            'emails' => $this->request->getBodyParam('emails', []),
            'email_id' => $this->request->getBodyParam('email_id'),
            'useQueue' => $this->request->getBodyParam('useQueue', true),
            'saveLogs' => $this->request->getBodyParam('saveLogs', false),
        ]);
        if ($shot->validate()) {
            Emails::$plugin->emailShots->send($shot);
            if ($shot->useQueue) {
                \Craft::$app->session->setNotice(\Craft::t('emails', '{number} emails have been sent to the queue.', ['number' => $shot->emailCount]));
            } else {
                \Craft::$app->session->setNotice(\Craft::t('emails', '{number} emails sent.', ['number' => $shot->emailCount]));
            }
            return $this->redirect(UrlHelper::cpUrl('emails/shots'));
        }
        return $this->actionQuickShot($shot);
    }

    public function actionLogs(int $id)
    {
        $shot = Emails::$plugin->emailShots->getById($id);
        $orderSide = $this->request->getParam('orderSide', 'desc');
        $order = $this->request->getParam('order', 'dateCreated');
        list($models, $pages) = Emails::$plugin->emailShots->getLogs($shot, $order, $orderSide);
        return $this->renderTemplate('emails/shot-logs', [
            'shot' => $shot,
            'logs' => $models,
            'pages' => $pages
        ]);
    }

    public function actionDeleteLogs()
    {
        $this->requirePermission('deleteEmailLogs');
        $id = $this->request->getRequiredParam('id');
        $shot = Emails::$plugin->emailShots->getById($id);
        $ids = $this->request->getParam('ids');
        Emails::$plugin->emailShots->deleteLogs($shot, $ids);
        \Craft::$app->session->setNotice(\Craft::t('emails', 'Logs have been deleted.'));
        return true;
    }

    protected function allEmails()
    {
        $emails = [];
        foreach (Emails::$plugin->emails->all() as $email) {
            $emails[$email->id] = $email->heading;
        }
        return $emails;
    }

    protected function allSources(): array
    {
        return array_map(function ($source) {
            return $source->name;
        }, Emails::$plugin->emailSources->all());
    }
}