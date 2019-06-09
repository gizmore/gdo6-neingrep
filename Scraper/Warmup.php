<?php
namespace GDO\NeinGrep\Scraper;

use GDO\NeinGrep\Scraper;
use GDO\Net\HTTP;
use GDO\Core\Logger;

/**
 * Warmup to get a cookie
 * @author gizmore
 */
final class Warmup extends Scraper
{
	public function scrapeWarmup()
	{
		Logger::logCron("Warmup request.");
		$this->beforeRequest();
		HTTP::getFromURL($this->neinURL());
	}
}
