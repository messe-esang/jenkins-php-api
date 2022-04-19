<?php

namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Exceptions\JenkinsApiException;
use JenkinsApi\Exceptions\NodeNotFoundException;
use JenkinsApi\Jenkins;
use RuntimeException;
use stdClass;

/**
 * Represents a node
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 *
 * @method null|stdClass getOfflineCause() null when computer is launching, tdClass when computer has been put offline
 */
class Node extends AbstractItem
{
    /**
     * @var string
     */
    private $_nodeName;

    /**
     * @param string  $nodeName
     * @param Jenkins $jenkins
     */
    public function __construct($nodeName, Jenkins $jenkins)
    {
        if ($nodeName === 'master') {
            $nodeName = '(master)';
        }

        $this->_nodeName = $nodeName;
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    public function refresh()
    {
        try {
            return parent::refresh();
        } catch (JenkinsApiException $e) {
            throw new NodeNotFoundException($this->_nodeName, 0, $e);
        }
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('computer/%s/api/json', rawurlencode($this->_nodeName));
    }

    /**
     * @return Executor[]
     */
    public function getExecutors()
    {
        return array_map(function ($i) {
            return new Executor($i - 1, $this->_nodeName, $this->getJenkins());
        }, range(1, $this->get('numExecutors')));
    }

    /**
     * @return Node
     */
    public function toggleOffline()
    {
        $response = $this->getJenkins()->post(sprintf('computer/%s/toggleOffline', $this->_nodeName));
        if ($response) {
            throw new RuntimeException(sprintf('Error marking %s offline', $this->_nodeName));
        }
        return $this;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->getJenkins()->post(sprintf('computer/%s/doDelete', $this->_nodeName));
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->getJenkins()->get(
            sprintf('/computer/%s/config.xml', $this->_nodeName),
            [],
            [CURLOPT_RETURNTRANSFER => 1]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->get('displayName');
    }

    /**
     *
     * @return bool
     */
    public function isOffline()
    {
        return (bool) $this->get('offline');
    }
}
