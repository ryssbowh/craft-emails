<?php 

namespace Ryssbowh\CraftEmails\exceptions;

class MailchimpException extends \Exception
{
    public static function noList(string $id)
    {
        return new static('Mailchimp list with id '.$id.' doesn\'t exist');
    }
}