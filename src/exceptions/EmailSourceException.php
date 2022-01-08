<?php 

namespace Ryssbowh\CraftEmails\exceptions;

use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;

class EmailSourceException extends \Exception
{
    public static function noHandle(string $handle)
    {
        return new static('Email source with handle '.$handle.' doesn\'t exist');
    }

    public static function defined(EmailSourceInterface $source)
    {
        return new static('Email source with handle '.$source->handle.' is already defined');
    }
}