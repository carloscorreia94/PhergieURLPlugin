<?php

/**
 * Phergie (http://phergie.org)
 *
 * @link http://github.com/phergie/phergie-irc-plugin-react-autojoin for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\AutoJoin
 */
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Event\EventInterface;
use Phergie\Irc\Client\React\LoopAwareInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

/**
 * Plugin for automatically joining channels.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\AutoJoin
 */
class HTTPStatusVerifier extends AbstractPlugin implements LoopAwareInterface
{
    /**
     * channel to join
     *
     * @var string
     */
    protected $channel;
 
    /**
     * url to test
     *
     * @var string
     */
    protected $url;
    /**
     * Have we already joined?
     * @var boolean
     */
    protected $joined = false;
    /**
    * Queue we get from the first join channel
    * @var EventQueueInterface 
    */
    protected $queue;
    /*
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!isset($config['channel'])) {
            throw new \DomainException('$config must contain a "channel" key');
        }
        if(!isset($config['url'])) {
            throw new \DomainException('$config must contain a "url" key');
        }
        $this->channel = $config['channel'];
        $this->url = $config["url"];
    }
    /**
     * Indicates that the plugin monitors events indicating either:
     * - a NickServ auth event (if wait-for-nickserv is set); or
     * - an end or lack of a message of the day,
     * at which point the client should be authenticated and
     * in a position to join channels.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
                'irc.received.rpl_endofmotd' => 'joinChannel',
                'irc.received.err_nomotd' => 'joinChannel',
            );

    }
    /**
     * Joins the channel.
     *
     * @param mixed $dummy Unused, as it only matters that one of the
     *        subscribed events has occurred, not what it is
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function joinChannel($dummy, Queue $queue)
    {
        $queue->ircJoin($this->channel, null);
        $this->joined = true;
        $this->queue = $queue;
        /*
        * This is really not pretty implementation but as our queue of events in the IRC channel doesn't really matters for this implementation we can pick up the initial one (at least I assume)
        */

        self::myTimerCallback(null);
        /*
        * We call this by the very first time in order to test out without waiting for the 5 minutes
        */
    }


    public function setLoop(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(60*5, array($this, 'myTimerCallback'));
    }

    public function myTimerCallback(TimerInterface $timer = null)
    {
        if($this->joined) {
                $queue = $this->queue;
                $channel = $this->channel;

                $this->emitter->emit('http.request', [new \Phergie\Plugin\Http\Request([
                    'url' => $this->url,                     // Required
                    'resolveCallback' => function($response) use ($queue,$channel) { // Required
                        if($response->getStatusCode() == 200) {
                            $message = 'Response status for url ' . $this->url . ' is 200!! OK';
                        } else {
                            $message = 'Response status for url ' . $this->url . ' is NOT 200. FAIL - Code was ' . $response->getStatusCode();
                        }

                        $queue->ircPrivmsg($channel, $message);

                    },
                    'method' => 'GET',                                  // Optional, request method
                    'headers' => array(),                               // Optional, headers for the request
                    'body' => '',                                       // Optional, request body to write after the headers
                    'rejectCallback' => function($error) use ($queue,$channel) {
                                $message = 'Error performing http request!';
                                $queue->ircPrivmsg($channel, $message);

                    },           
                ])]);

        }
    }

}
