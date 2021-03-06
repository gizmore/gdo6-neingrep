<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO_Module;
use GDO\DB\GDT_Float;
use GDO\DB\GDT_UInt;
use GDO\Form\GDT_Select;
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;
use GDO\DB\GDT_Checkbox;

/**
 * Scrapes 9gag.com for own statistic purposes.
 * 
 * @author gizmore
 * @license properitary
 */
final class Module_NeinGrep extends GDO_Module
{
	public $module_license = 'properitary';
	public $module_priority = 96;
	public function isSiteModule() { return true; }
	
	public function onLoadLanguage() { return $this->loadLanguage('lang/neingrep'); }
	
	public function href_administrate_module() { return href('NeinGrep', 'Admin'); }
	
	public function getDependencies() { return ['Material', 'JQuery', 'File', 'Captcha', 'Admin', 'Account', 'Profile', 'Contact', 'News', 'Login', 'Address']; }
	
	public function getClasses()
	{
		return array(
			'GDO\\NeinGrep\\NG_Section',
			'GDO\\NeinGrep\\NG_User',
			'GDO\\NeinGrep\\NG_UserSectionStats',
			'GDO\\NeinGrep\\NG_Post',
			'GDO\\NeinGrep\\NG_Comment',
			'GDO\\NeinGrep\\NG_CommentLike',
			'GDO\\NeinGrep\\NG_PostLike',
		);
	}
	
	##############
	### Config ###
	##############
	public function getConfig()
	{
		return array(
			GDT_Float::make('ng_request_sleep')->initial('5.0'),
			GDT_UInt::make('ng_request_count')->writable(false)->editable(false)->initial('0'),
// 			GDT_Duration::make('ng_scrape_max_age')->initial(Time::ONE_YEAR*2),
			GDT_Select::make('ng_banned_sections')->choices(NG_Section::table()->all())->multiple()->initial('[]'),
			GDT_Select::make('ng_allowed_sections')->choices(NG_Section::table()->all())->multiple()->initial('[]'),
			GDT_Checkbox::make('ng_scrape_fresh_only')->initial('1'), # overrides those two above
		);
	}
	
	public function cfgRequestCount() { return $this->getConfigVar('ng_request_count'); }
	public function cfgRequestSleep() { return $this->getConfigValue('ng_request_sleep'); }
	public function cfgRequestSleepMicros() { return intval($this->cfgRequestSleep()*1000000); }
// 	public function cfgScrapeMaxAge() { return $this->getConfigValue('ng_scrape_max_age'); }
	public function cfgBannedSections() { return $this->getConfigValue('ng_banned_sections'); }
	public function cfgAllowedSections() { return $this->getConfigValue('ng_allowed_sections'); }
	public function cfgOnlyFresh() { return $this->getConfigValue('ng_scrape_fresh_only'); }
	
	public function increaseRequestCounter() { return $this->saveConfigVar('ng_request_count', $this->cfgRequestCount()+1); }
	
	#############
	### Hooks ###
	#############
	public function hookTopBar(GDT_Bar $navbar)
	{
		$navbar->addField(GDT_Link::make('ng_link_welcome')->href(href('NeinGrep', 'Welcome')));
// 		$navbar->addField(GDT_Link::make('ng_link_add_post')->href(href('NeinGrep', 'AddPost')));
// 		$navbar->addField(GDT_Link::make('ng_link_add_user')->href(href('NeinGrep', 'AddUser')));
	}
	
}
