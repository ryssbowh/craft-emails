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
     * Emails getter
     * 
     * @return array
     */
    public function getEmails(): array;
}