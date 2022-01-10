<?php

namespace Ryssbowh\CraftEmails\services;

use Ryssbowh\CraftEmails\events\RegisterEmailSourcesEvent;
use Ryssbowh\CraftEmails\exceptions\EmailSourceException;
use Ryssbowh\CraftEmails\interfaces\EmailSourceInterface;
use craft\base\Component;

class EmailSourceService extends Component
{   
    const EVENT_REGISTER = 'event_register';
    
    /**
     * @var EmailSourceInterface[]
     */
    protected $_sources;

    /**
     * Get all emails
     * 
     * @return array
     */
    public function all(): array
    {
        if ($this->_sources === null) {
            $this->register();
        }
        return $this->_sources;
    }

    /**
     * Get an email source by handle
     * 
     * @param  string $handle
     * @return EmailSourceInterface
     */
    public function getByHandle(string $handle): EmailSourceInterface
    {
        if (isset($this->all()[$handle])) {
            return $this->all()[$handle];
        }
        throw EmailSourceException::noHandle($handle);
    }

    /**
     * Does a handle exists
     * 
     * @param  string  $handle
     * @return boolean
     */
    public function has(string $handle): bool
    {
        return isset($this->all()[$handle]);
    }

    /**
     * Register email sources
     */
    protected function register()
    {
        $event = new RegisterEmailSourcesEvent();
        $this->trigger(self::EVENT_REGISTER, $event);
        $this->_sources = $event->sources;
    }

    /**
     * Trigger an event
     * 
     * @param string $type
     * @param Event  $event
     */
    protected function triggerEvent(string $type, Event $event) 
    {
        if ($this->hasEventHandlers($type)) {
            $this->trigger($type, $event);
        }
    }
}
