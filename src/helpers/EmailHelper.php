<?php

namespace Ryssbowh\CraftEmails\helpers;

class EmailHelper
{   
    /**
     * Parse an array (or string separated by commas) of emails,
     * replacing env variables in them
     * 
     * @param  string|array $emails
     * @return array
     */
    public static function parseEmails($emails): array
    {
        if (!is_array($emails)) {
            $emails = explode(',', \Craft::parseEnv($emails));
        }
        return array_map(function ($email) {
            return \Craft::parseEnv(trim($email));
        }, $emails);
    }
}