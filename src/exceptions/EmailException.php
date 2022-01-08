<?php 

namespace Ryssbowh\CraftEmails\exceptions;

class EmailException extends \Exception
{
    public static function noId(int $id)
    {
        return new static('Email with id '.$id.' doesn\'t exist');
    }

    public static function noLogId(int $id)
    {
        return new static('Email log with id '.$id.' doesn\'t exist');
    }
}