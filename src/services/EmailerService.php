<?php

namespace Ryssbowh\CraftEmails\services;

use Craft;
use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\records\EmailLog;
use Ryssbowh\CraftEmails\helpers\EmailHelper;
use craft\elements\Asset;
use craft\helpers\App;
use craft\helpers\Template;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\web\View;
use yii\helpers\Markdown;
use yii\mail\MailEvent;

class EmailerService extends Mailer
{
    const EVENT_BEFORE_PREP = 'beforePrep';

    public $parentMailer;

    /**
     * @inheritdoc
     */
    public function send($message): bool
    {
        // fire a beforePrep event
        $this->trigger(self::EVENT_BEFORE_PREP, new MailEvent([
            'message' => $message,
        ]));

        $mail = $message->key ? Emails::$plugin->emails->getByKey($message->key) : null;
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $settings = App::mailSettings();
        $view = Craft::$app->getView();

        if ($message instanceof Message && $message->key !== null and $mail !== null) {
            if ($message->language === null) {
                // Default to the current language
                $message->language = Craft::$app->getRequest()->getIsSiteRequest()
                    ? Craft::$app->language
                    : Craft::$app->getSites()->getPrimarySite()->language;
            }

            $systemMessage = Craft::$app->getSystemMessages()->getMessage($message->key, $message->language);

            // Use the message language
            $language = Craft::$app->language;
            Craft::$app->language = $message->language;

            $fromEmail = $mail->from ? \Craft::parseEnv($mail->from) : \Craft::parseEnv($settings->fromEmail);
            $fromName = $mail->fromName ? \Craft::parseEnv($mail->fromName) : \Craft::parseEnv($settings->fromName);
            $replyToEmail = $mail->replyTo ? \Craft::parseEnv($mail->replyTo) : \Craft::parseEnv($settings->replyToEmail);
            $message->setFrom([$fromEmail => $fromName]);
            if ($replyToEmail) {
                $message->setReplyTo($replyToEmail);
            }
            if ($mail->bcc) {
                $oldBcc = $message->getBcc();
                $oldBcc = $oldBcc ? (is_array($oldBcc) ? $oldBcc : [$oldBcc => '']) : [];
                $bcc = array_merge($oldBcc, EmailHelper::parseEmails($mail->bcc));
                $message->setBcc($bcc);
            }
            if ($mail->cc) {
                $oldCc = $message->getCc();
                $oldCc = $oldCc ? (is_array($oldCc) ? $oldCc : [$oldCc => '']) : [];
                $cc = array_merge($oldCc, EmailHelper::parseEmails($mail->cc));
                $message->setCc($cc);
            }
            $attachements = Emails::$plugin->attachements->get($message->key, $message->language, true);
            foreach ($attachements as $asset) {
                $message->attachContent($asset->getContents(), [
                    'fileName' => $asset->filename
                ]);
            }

            $settings = App::mailSettings();
            $variables = ($message->variables ?: []) + [
                'emailKey' => $message->key,
                'fromEmail' => $fromEmail,
                'replyToEmail' => $replyToEmail,
                'fromName' => $fromName,
            ];

            // Temporarily disable lazy transform generation
            $generateTransformsBeforePageLoad = $generalConfig->generateTransformsBeforePageLoad;
            $generalConfig->generateTransformsBeforePageLoad = true;

            // Render the subject and body text
            $subject = $view->renderString($systemMessage->subject, $variables, View::TEMPLATE_MODE_SITE);
            $body = $view->renderString($systemMessage->parsedBody, $variables, View::TEMPLATE_MODE_SITE);

            $message->setSubject($subject);
            $html = $view->renderTemplate($mail->template, array_merge($variables, [
                'body' => Template::raw(Markdown::process($body)),
            ]), View::TEMPLATE_MODE_SITE);
            if (!$mail->plain) {
                $message->setHtmlBody($html);
            }
            // Remove </> from around URLs, so they’re not interpreted as HTML tags
            $textBody = preg_replace('/<(https?:\/\/.+?)>/', '$1', $html);
            $message->setTextBody(strip_tags($textBody));

            // Set things back to normal
            Craft::$app->language = $language;
            $generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;
        }

        // Set the default sender if there isn't one already
        if (!$message->getFrom()) {
            $message->setFrom($this->from);
        }

        if ($this->replyTo && !$message->getReplyTo()) {
            $message->setReplyTo($this->replyTo);
        }

        $isSuccessful = $this->_send($message);
        $this->afterSend($message, $isSuccessful);
        return $isSuccessful;
    }

    /**
     * Resend a message without going through the process of rebuilding html and all other parameters
     * in case those parameters have changed since the email was sent.
     * 
     * @param  string $key
     * @param  string $subject
     * @param  string $textBody
     * @param  string $htmlBody
     * @param  array  $from
     * @param  array  $replyTo
     * @param  array  $bcc
     * @param  array  $cc
     * @param  array  $to
     * @param  array  $attachements
     * @return bool
     */
    public function resend(string $key, string $subject, string $textBody, string $htmlBody, array $from, array $replyTo, array $bcc, array $cc, array $to, array $attachements): bool
    {
        $message = \Craft::createObject([
            'class' => $this->messageClass,
            'mailer' => $this,
            'key' => $key
        ]);
        $message->setSubject($subject);
        $message->setTextBody($textBody);
        $message->setFrom($from);
        $message->setReplyTo($replyTo);
        $message->setBcc($bcc);
        $message->setCc($cc);
        $message->setTo($to);
        $message->setHtmlBody($htmlBody);
        if ($attachements) {
            foreach (Asset::find()->id($attachements)->all() as $asset) {
                $message->attachContent($asset->getContents(), [
                    'fileName' => $asset->filename
                ]);
            }
        }

        $isSuccessful = $this->_send($message);
        $this->afterSend($message, $isSuccessful, $attachements);
        return $isSuccessful;
    }

    /**
     * @inheritDoc
     * @param  Message    $message
     * @param  bool       $isSuccessful
     * @param  array|null $attachements
     */
    public function afterSend($message, $isSuccessful, ?array $attachements = null)
    {
        $mail = $message->key ? Emails::$plugin->emails->getByKey($message->key) : null;
        if ($mail) {
            $record = Emails::$plugin->emails->getRecordById($mail->id);
            if ($isSuccessful and $record) {
                $record->sent = $record->sent + 1;
                $record->save(false);
                if ($record->saveLogs) {
                    if ($mail->plain) {
                        $body = $message->getBody();
                    } else {
                        $body = $message->getHtmlBody();
                    }
                    if ($attachements === null) {
                        $attachements = Emails::$plugin->attachements->get($message->key, $message->language);
                    }
                    $user = \Craft::$app->getUser()->getIdentity();
                    $log = new EmailLog([
                        'email_id' => $record->id,
                        'subject' => $message->getSubject(),
                        'to' => (array) $message->getTo(),
                        'bcc' => $message->getBcc() ? (array) $message->getBcc() : [],
                        'cc' => $message->getCc() ? (array) $message->getCc() : [],
                        'from' => $message->getFrom(),
                        'attachements' => $attachements,
                        'replyTo' => $message->getReplyTo(),
                        'user_id' => $user ? $user->id : null,
                        'is_console' => \Craft::$app->request->isConsoleRequest,
                        'body' => $body
                    ]);
                    $log->save(false);
                }
            }
        }
        parent::afterSend($message, $isSuccessful);
    }

    /**
     * Do the actual sending
     * 
     * @param  Message $message
     * @return bool
     */
    private function _send(Message $message): bool
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        // Apply the testToEmailAddress config setting
        $testToEmailAddress = $generalConfig->getTestToEmailAddress();
        if (!empty($testToEmailAddress)) {
            $message->setTo($testToEmailAddress);
            $message->setCc(null);
            $message->setBcc(null);
        }

        try {
            if (!$this->beforeSend($message)) {
                return false;
            }
            $address = $message->getTo();
            if (is_array($address)) {
                $address = implode(', ', array_keys($address));
            }
            \Yii::info('Sending email "' . $message->getSubject() . '" to "' . $address . '"', __METHOD__);
            if ($this->useFileTransport) {
                $isSuccessful = $this->saveMessage($message);
            } else {
                $isSuccessful = $this->sendMessage($message);
            }
            return $isSuccessful;
        } catch (\Throwable $e) {
            $eMessage = $e->getMessage();

            // Remove the stack trace to get rid of any sensitive info. Note that Swiftmailer includes a debug
            // backlog in the exception message. :-/
            $eMessage = substr($eMessage, 0, strpos($eMessage, 'Stack trace:') - 1);
            Craft::warning('Error sending email: ' . $eMessage);

            // Save the exception on the message, for plugins to make use of
            if ($message instanceof Message) {
                $message->error = $e;
            }
            return false;
        }
    }
}
