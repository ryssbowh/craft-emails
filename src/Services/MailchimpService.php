<?php

namespace Ryssbowh\CraftEmails\Services;

use DrewM\MailChimp\MailChimp;
use Ryssbowh\CraftEmails\Emails;
use Ryssbowh\CraftEmails\Models\MailchimpList;
use Ryssbowh\CraftEmails\Models\MailchimpMember;
use Ryssbowh\CraftEmails\exceptions\MailchimpException;
use craft\base\Component;

class MailchimpService extends Component
{
    const CACHE_KEY = 'emails.mailchimp_lists';

    /**
     * @var MailChimp
     */
    protected $_mailchimp;

    /**
     * @var array
     */
    protected $_lists;

    /**
     * Is the api up and running
     * 
     * @return boolean
     */
    public function isEnabled(): bool
    {
        return !is_null($this->api);
    }

    /**
     * Clear mailchimp caches
     */
    public function clearCaches()
    {
        \Craft::$app->cache->delete(self::CACHE_KEY);
    }

    /**
     * Get all lists
     * 
     * @return array
     */
    public function getLists(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        if ($this->_lists === null) {
            $cached = \Craft::$app->cache->get(self::CACHE_KEY);
            if ($cached === false) {
                $lists = $this->api->get('lists');
                $cached = [];
                foreach ($lists['lists'] ?? [] as $attributes) {
                    $list = new MailchimpList($attributes);
                    $list->members = [];
                    $details = $this->api->get('lists/' . $list->id . '/members');
                    foreach ($details['members'] as $member) {
                        $list->members[] = new MailchimpMember($member);
                    }
                    $cached[$attributes['id']] = $list;
                }
                $duration = Emails::$plugin->settings->mailchimpCacheDuration;
                \Craft::$app->cache->set(self::CACHE_KEY, $cached, $duration * 60);
            }
            $this->_lists = $cached;
        }
        return $this->_lists;
    }

    /**
     * Get a list by id
     * 
     * @param  string $id
     * @return MailchimpList
     */
    public function getList(string $id): MailchimpList
    {
        if (isset($this->lists[$id])) {
            return $this->lists[$id];
        }
        throw MailchimpException::noList($id);
    }

    /**
     * Get api instance
     * 
     * @return ?MailChimp
     */
    public function getApi(): ?MailChimp
    {
        if (is_null($this->_mailchimp)) {
            if (Emails::$plugin->settings->mailchimpApiKey) {
                $this->_mailchimp = new MailChimp(\Craft::parseEnv(Emails::$plugin->settings->mailchimpApiKey));
            }
        }
        return $this->_mailchimp;
    }
}
