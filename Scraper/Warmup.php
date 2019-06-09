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
		HTTP::getFromURL($this->neinURL());
		Logger::logCron("Warmup request...\n");
		$this->sleep();
	}
}
