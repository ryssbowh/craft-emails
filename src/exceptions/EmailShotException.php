<?php 

namespace Ryssbowh\CraftEmails\exceptions;

class EmailShotException extends \Exception
{
    public static function noId(int $id)
    {
        return new static('Email shot with id '.$id.' doesn\'t exist');
    }

    public static function noHandle(string $handle)
    {
        return new static('Email shot with handle '.$handle.' doesn\'t exist');
    }

    public static function noIdRecord(int $id)
    {
        return new static('Email shot record with id '.$id.' doesn\'t exist');
    }
}