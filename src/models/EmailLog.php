<?php 

namespace Ryssbowh\CraftEmails\models;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\records\Email as EmailRecord;
use craft\base\Model;
use craft\elements\Asset;
use craft\elements\User;

class EmailLog extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uid;

    /**
     * @var \DateTime
     */ 
    public $dateCreated;

    /**
     * @var \DateTime
     */
    public $dateUpdated;

    /**
     * @var int
     */
    public $email_id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var boolean
     */
    public $is_console;

    /**
     * @var array
     */
    protected $_attachements;

    /**
     * @var array
     */
    protected $_from;

    /**
     * @var array
     */
    protected $_replyTo;

    /**
     * @var array
     */
    protected $_bcc;

    /**
     * @var array
     */
    protected $_cc;

    /**
     * @var array
     */
    protected $_to;

    public function defineRules(): array
    {
        return [
            [['id', 'email_id', 'uid', 'dateCreated', 'dateUpdated', 'attachements', 'from'], 'safe'],
            [['email', 'bcc', 'subject', 'cc', 'replyTo'], 'string']
        ];
    }

    /**
     * User getter
     * 
     * @return ?User
     */
    public function getUser(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find()->id($this->user_id)->one();
    }

    /**
     * Get attachements as elements (assets)
     * 
     * @return array
     */
    public function getAttachementsElements(): array
    {
        if (!$this->attachements) {
            return [];
        }
        return Asset::find()->id($this->attachements)->all();
    }

    /**
     * Attachements setter
     * 
     * @param array|string $attachements
     */
    public function setAttachements($attachements)
    {
        if (is_string($attachements)) {
            $attachements = json_decode($attachements, true);
        }
        if (is_null($attachements)) {
            $attachements = [];
        }
        $this->_attachements = $attachements;
    }

    /**
     * From setter
     * 
     * @param array|string $from
     */
    public function setFrom($from)
    {
        if (is_string($from)) {
            $from = json_decode($from, true);
        }
        $this->_from = $from;
    }

    /**
     * To setter
     * 
     * @param array|string $to
     */
    public function setTo($to)
    {
        if (is_string($to)) {
            $to = json_decode($to, true);
        }
        $this->_to = $to;
    }

    /**
     * Bcc setter
     * 
     * @param array|string $bcc
     */
    public function setBcc($bcc)
    {
        if (is_string($bcc)) {
            $bcc = json_decode($bcc, true);
        }
        if (is_null($bcc)) {
            $bcc = [];
        }
        $this->_bcc = $bcc;
    }

    /**
     * Cc setter
     * 
     * @param array|string $cc
     */
    public function setCc($cc)
    {
        if (is_string($cc)) {
            $cc = json_decode($cc, true);
        }
        if (is_null($cc)) {
            $cc = [];
        }
        $this->_cc = $cc;
    }

    /**
     * Reply to setter
     * 
     * @param array|string $to
     */
    public function setReplyTo($to)
    {
        if (is_string($to)) {
            $to = json_decode($to, true);
        }
        $this->_replyTo = $to;
    }

    /**
     * From getter
     * 
     * @return array
     */
    public function getFrom(): array
    {
        return $this->_from;
    }

    /**
     * To getter
     * 
     * @return array
     */
    public function getTo(): array
    {
        return $this->_to;
    }

    /**
     * Attachements getter
     * 
     * @return array
     */
    public function getAttachements(): array
    {
        return $this->_attachements;
    }

    /**
     * Reply to getter
     * 
     * @return array
     */
    public function getReplyTo(): array
    {
        return $this->_replyTo;
    }

    /**
     * Bcc getter
     * 
     * @return array
     */
    public function getBcc(): array
    {
        return $this->_bcc;
    }

    /**
     * Cc getter
     * 
     * @return array
     */
    public function getCc(): array
    {
        return $this->_cc;
    }

    /**
     * Email getter
     * 
     * @return ?Email
     */
    public function getEmail(): ?Email
    {
        if ($this->email_id) {
            return Emails::$plugin->emails->getById($this->email_id);
        }
        return null;
    }

    /**
     * Body getter
     * 
     * @return string
     */
    public function getBody(): string
    {
        $file = \Craft::getAlias('@storage/logs/emails/' . $this->uid);
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return '';
    }

    /**
     * Text body getter
     * 
     * @return string
     */
    public function getTextBody(): string
    {
        return strip_tags($this->body);
    }

    /**
     * @inheritDoc
     */
    public function fields(): array
    {
        return ['subject', 'to', 'from', 'replyTo', 'bcc', 'cc', 'from', 'body'];
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $array = parent::toArray($fields, $expand, $recursive);
        $array['attachements'] = [];
        foreach ($this->attachements as $id) {
            $asset = Asset::find()->id($id)->one();
            if ($asset) {
                $array['attachements'][] = [
                    'url' => $asset->url,
                    'title' => $asset->title
                ];
            } else {
                $array['attachements'][] = [
                    'deleted' => true,
                    'title' => \Craft::t('emails', 'Asset deleted from file system')
                ];
            }
        }
        return $array;
    }
}