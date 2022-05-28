<?php

namespace Ryssbowh\CraftEmails\console\controllers;

use Ryssbowh\CraftEmails\Emails;
use craft\console\Controller;
use yii\console\ExitCode;

class ShotController extends Controller
{
    /**
     * @var bool Run the queue (only applies to email shots that use it)
     */
    public $runQueue = false;

    /**
     * @var bool Override the useQueue parameter of the email shot
     */
    public $forceQueue;

    /**
     * Send an email shot
     *
     * @param string $handle Handle of the email shot
     * @return int
     */
    public function actionSend(string $handle)
    {
        $shot = Emails::$plugin->emailShots->getByHandle($handle);
        if ($this->forceQueue !== null) {
            $this->forceQueue = (bool)$this->forceQueue;
        }
        Emails::$plugin->emailShots->send($shot, $this->forceQueue);
        $usedQueue = $this->forceQueue ?? $shot->useQueue;
        if ($usedQueue) {
            $this->stdout(\Craft::t('emails', '{number} emails have been sent to the queue.', ['number' => $shot->emailCount]) . "\n");
            if ($this->runQueue) {
                \Craft::$app->queue->run();
                $this->stdout(\Craft::t('emails', 'Queue has been run.') . "\n");
            }
        } else {
            list($sent, $failed) = Emails::$plugin->emailShots->lastRunResult;
            if (sizeof($sent)) {
                $this->stdout(\Craft::t('emails', '{number} emails sent.', ['number' => sizeof($sent)]) . "\n");
            }
            if (sizeof($failed)) {
                $this->stderr(\Craft::t('emails', '{number} emails failed to send.', ['number' => sizeof($failed)]) . "\n");
            }
        }

        return ExitCode::OK;
    }

    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'runQueue';
        $options[] = 'forceQueue';
        return $options;
    }
}