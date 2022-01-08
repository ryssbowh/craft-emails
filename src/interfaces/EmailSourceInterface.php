<?php

namespace Ryssbowh\CraftEmails\interfaces;

interface EmailSourceInterface
{
    /**
     * Name getter
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Handle getter
     * 
     * @return string
     */
    public function getHandle(): string;

    /**
     * Emails getter, must return an array of this form :
     *
     * [
     *     'email@domain.com' => 'Name',
     *     'email2@domain.com' => null
     * ]
     * 
     * @return array
     */
    public function getEmails(): array;
}