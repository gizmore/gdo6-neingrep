<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_AutoInc;
use GDO\DB\GDT_String;
use GDO\Util\Strings;
use GDO\Date\GDT_DateTime;
use GDO\Core\Logger;
use GDO\DB\GDT_UInt;
use GDO\DB\GDT_Checkbox;

final class NG_Section extends GDO
{
	public function gdoCached() { return true; }
	
	public function gdoColumns()
	{
		return array(
			GDT_AutoInc::make('ngs_id'),
			GDT_String::make('ngs_name')->notNull()->unique()->max(64),
			GDT_String::make('ngs_title')->max(128)->binary(),
			GDT_UInt::make('ngs_posts')->notNull()->initial('0'),
			GDT_String::make('ngs_cursor_front')->max(128)->ascii()->caseS(),
			GDT_String::make('ngs_cursor_back')->max(128)->ascii()->caseS(),
			GDT_DateTime::make('ngs_scraped'),
			GDT_Checkbox::make('ngs_scrape_finished')->notNull()->initial('0'),
		);
	}
	
	public function getName() { return $this->getVar('ngs_name'); }
	public function getTitle() { return $this->getVar('ngs_title'); }
	public function isFinished() { return $this->getValue('ngs_scrape_finished'); }
	
	public function hasParticipated(NG_User $user)
	{
		return NG_Post::table()->countWhere("ngp_section={$this->getID()} AND ngp_creator={$user->getID()}") ||
		       NG_Comment::table()->select('COUNT(*)')->joinObject('ngc_post')->where("ngp_section={$this->getID()} AND ngc_user={$user->getID()}")->exec()->fetchValue();
	}
	
	##############
	### Render ###
	##############
	public function displayTitle() { return html($this->getVar('ngs_title')); }
	public function renderCell() { return $this->displayTitle(); }
	public function renderChoice() { return $this->displayTitle(); }
	
	##############
	### Static ###
	##############
	public static function getNameFromURL(string $url)
	{
		return Strings::rsubstrFrom($url, '/');
	}
	
	public static function getOrCreate(array $data)
	{
		$url = $data['url'];
		$name = self::getNameFromURL($url);
		$title = $data['name'];
		return self::getOrCreateSection($name, $title);
	}
	
	public static function getOrCreateSection($name, $title='')
	{
		if (!($section = self::getBy('ngs_name', $name)))
		{
			$section = self::createSection($name, $title?$title:$name);
		}
		return $section;
	}
	
	public static function createSection($name, $title)
	{
		Logger::logCron("New NG_Section $name/$title");
		return self::blank(array(
			'ngs_name' => $name,
			'ngs_title' => $title,
		))->insert();
	}
	
	#############
	### Cache ###
	#############
	private static $ALL_CACHE = null;
	public static function allSections()
	{
		if (self::$ALL_CACHE === null)
		{
			self::$ALL_CACHE = self::table()->select('ngs_name, ngs_title')->order('ngs_title')->exec()->fetchAllArray2dPair();
			uasort(self::$ALL_CACHE, function($a, $b){
				return strcasecmp($a, $b);
			});
		}
		return self::$ALL_CACHE;
	}
	
	private static $ALL_CACHE2 = null;
	/**
	 * @return self[]
	 */
	public function all()
	{
		if (self::$ALL_CACHE2 === null)
		{
			self::$ALL_CACHE2 = parent::all();
			uasort(self::$ALL_CACHE2, function($a, $b){
				return strcasecmp($a->getTitle(), $b->getTitle());
			});
		}
		return self::$ALL_CACHE2;
	}
	
}
