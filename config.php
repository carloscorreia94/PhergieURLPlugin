<?php

use Phergie\Irc\Connection;
require_once('HTTPStatusVerifier.php');


return array(
  'connections' => array(
    new Connection(array(
      'serverHostname' => 'irc.freenode.net',
      'username' => 'correiask8',
      'realname' => 'Carlos Correia',
      'nickname' => 'correiask8'
    )),
    ),
	'plugins' => array(
		new HTTPStatusVerifier(array(

            'channel' => '#testecarlosmouracorreia',
            'url' => 'https://www.thrashermagazine.com',
        )),
	new \Phergie\Plugin\Dns\Plugin,
	new \Phergie\Plugin\Http\Plugin

  ),
  );
