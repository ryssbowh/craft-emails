<?php 

namespace Ryssbowh\CraftEmails\models;

use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\models\Email;
use Ryssbowh\CraftEmails\Records\Email as EmailRecord;
use Ryssbowh\CraftEmails\Records\EmailShot as EmailShotRecord;
use Ryssbowh\CraftEmails\exceptions\EmailSourceException;
use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use craft\base\Model;
use craft\elements\User;

class EmailShotLog extends Model
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $uid;

    /**
     * @var DateTime
     */
    public $dateCreated;

    /**
     * @var DateTime
     */
    public $dateUpdated;

    /**
     * @var integer
     */
    public $shot_id;

    /**
     * @var integer
     */
    public $user_id;

    /**
     * @var array
     */
    protected $_emails;

    /**
     * @var boolean
     */
    public $is_console;

    /**
     * Emails setter
     * 
     * @param string|array $emails
     */
    public function setEmails($emails)
    {
        if (is_string($emails)) {
            $emails = json_decode($emails, true);
        }
        if (is_null($emails)) {
            $emails = [];
        }
        $this->_emails = $emails;
    }

    /**
     * Emails getter
     * 
     * @return array
     */
    public function getEmails(): array
    {
        return $this->_emails;
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
     * Email shot getter
     * 
     * @return ?EmailShot
     */
    public function getShot(): ?EmailShot
    {
        if ($this->shot_id) {
            return Emails::$plugin->emailShots->getById($this->shot_id);
        }
        return null;
    }
}