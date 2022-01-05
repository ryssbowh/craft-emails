<?php 

namespace Ryssbowh\CraftEmails\Models;

use Ryssbowh\CraftEmails\Records\Email as EmailRecord;
use craft\base\Model;

class EmailLog extends Model
{
    public $id;   
    public $uid;   
    public $dateCreated;
    public $dateUpdated;
    public $email_id;
    public $email;
    public $subject;
    public $bcc;
    public $cc;
    public $content;

    public function defineRules(): array
    {
        return [
            [['id', 'email_id', 'uid', 'dateCreated', 'dateUpdated'], 'safe'],
            [['email', 'bcc', 'content', 'subject', 'cc'], 'string']
        ];
    }

    public function getUncompressedContent()
    {
        return gzinflate($this->content);
    }
}