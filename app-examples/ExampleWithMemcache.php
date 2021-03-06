<?php

/**
 * @package Examples
 * @subpackage Memcache
 *
 * @author Zorin Vasily <kak.serpom.po.yaitsam@gmail.com>
 */
class ExampleWithMemcache extends AppInstance {

	/**
	 * Called when the worker is ready to go.
	 * @return void
	 */

	public function onReady() {
		$this->memcache = Daemon::$appResolver->getInstanceByAppName('MemcacheClient');
	}
	
	/**
	 * Creates Request.
	 * @param object Request.
	 * @param object Upstream application instance.
	 * @return object Request.
	 */
	public function beginRequest($req, $upstream) {
		return new ExampleWithMemcacheRequest($this, $upstream, $req);
	}
	
}

class ExampleWithMemcacheRequest extends HTTPRequest {

	public $stime;
	public $queryResult;
	public $sql;
	public $runstate = 0;

	/**
	 * Constructor.
	 * @return void
	 */
	public function init() {
		$req = $this;
		$this->stime = microtime(true);
		$this->appInstance->memcache->stats(function($m) use ($req) {
			$req->queryResult = $m->result;
			$req->wakeup(); // wake up the request immediately
		});
	}

	/**
	 * Called when request iterated.
	 * @return integer Status.
	 */
	public function run() {
		if (
			!$this->queryResult 
			&& ($this->runstate++ === 0)
		) {
			// sleep for 5 seconds or until wakeup
			$this->sleep(5);
		} 
		
		try {
			$this->header('Content-Type: text/html');
		} catch (Exception $e) {}

			?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Example with Memcache</title>
</head>
<body>
<?php
if ($this->queryResult) {
	echo '<h1>It works! Be happy! ;-)</h1>Result of query: <pre>';
	var_dump($this->queryResult);
	echo '</pre>';
} else {
	echo '<h1>Something went wrong! We have no result.</h1>';
}
echo '<br />Request (http) took: '.round(microtime(TRUE)-$this->stime,6);
?>
</body>
</html>
<?php
	}
	
}
