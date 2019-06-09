<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO_Module;
use GDO\DB\GDT_Float;
use GDO\DB\GDT_UInt;
use GDO\DB\GDT_Checkbox;
use GDO\Date\GDT_Duration;
use GDO\Date\Time;

/**
 * Scrapes 9gag.com for own statistic purposes.
 * 
 * @author gizmore
 * @license properitary
 */
final class Module_NeinGrep extends GDO_Module
{
	public function getClasses()
	{
		return array(
			'GDO\\NeinGrep\\NG_Section',
			'GDO\\NeinGrep\\NG_User',
			'GDO\\NeinGrep\\NG_Post',
			'GDO\\NeinGrep\\NG_Comment',
			'GDO\\NeinGrep\\NG_CommentLike',
			'GDO\\NeinGrep\\NG_PostLike',
			'GDO\\NeinGrep\\NG_PostCommented',
		);
	}
	
	public function getConfig()
	{
		return array(
			GDT_Float::make('ng_request_sleep')->initial('8.0'),
			GDT_UInt::make('ng_request_count')->writable(false)->editable(false)->initial('0'),
			GDT_Duration::make('ng_scrape_max_age')->initial(Time::ONE_YEAR*2),
		);
	}
	
	public function cfgRequestCount() { return $this->getConfigVar('ng_request_count'); }
	public function cfgRequestSleep() { return $this->getConfigValue('ng_request_sleep'); }
	public function cfgRequestSleepMicros() { return intval($this->cfgRequestSleep()*1000000); }
	public function cfgScrapeMaxAge() { return $this->getConfigValue('ng_scrape_max_age'); }
	
}
