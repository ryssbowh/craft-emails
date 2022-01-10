<?php 

namespace Ryssbowh\CraftEmails\events;

use Ryssbowh\CraftEmails\exceptions\EmailSourceException;
use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use yii\base\Event;

class RegisterEmailSourcesEvent extends Event
{
    /**
     * @var EmailSourceInterface[]
     */
    protected $_sources = [];

    /**
     * Add an email source
     * 
     * @param EmailSourceInterface $source
     */
    public function add(EmailSourceInterface $source, bool $replaceIfExisting = false)
    {
        if (isset($this->_sources[$source->handle]) and !$replaceIfExisting) {
            throw EmailSourceException::defined($source);
        }
        $this->_sources[$source->handle] = $source;
    }

    /**
     * Sources getter
     * 
     * @return EmailSourceInterface[]
     */
    public function getSources(): array
    {
        return $this->_sources;
    }
}