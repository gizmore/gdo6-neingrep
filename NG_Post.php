<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_AutoInc;
use GDO\DB\GDT_String;
use GDO\DB\GDT_UInt;
use GDO\DB\GDT_Object;
use GDO\Date\GDT_DateTime;
use GDO\DB\GDT_Checkbox;
use GDO\Core\Logger;

final class NG_Post extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_AutoInc::make('ngp_id'),
			GDT_String::make('ngp_nid')->max(16)->caseS(),
			GDT_Object::make('ngp_section')->table(NG_Section::table()),
			GDT_Object::make('ngp_creator')->table(NG_User::table()),
			GDT_String::make('ngp_title')->binary()->caseS(),
			GDT_DateTime::make('ngp_created'),
			GDT_DateTime::make('ngp_scraped'),
			GDT_Checkbox::make('ngp_nsfw'),
			GDT_UInt::make('ngp_comments')->notNull()->initial('0'),
			GDT_UInt::make('ngp_upvotes')->notNull()->initial('0'),
			GDT_UInt::make('ngp_downvotes')->notNull()->initial('0'),
			GDT_String::make('ngp_comment_ref')->max(64)->ascii()->caseS(),
		);
	}
	
	public function getPostID() { return $this->getVar('ngp_nid'); }
	public function getTitle() { return $this->getVar('ngp_title'); }
	public function getCommentRef() { return $this->getVar('ngp_comment_ref'); }
	public function getCommentCount() { return $this->getValue('ngp_comments'); }
	public function displayTitle() { return $this->display('ngp_title'); }
	
	public function hrefGag() { return "http://9gag.com/gag/{$this->getPostID()}"; }
	
	
	/**
	 * @return NG_User
	 */
	public function getUser() { return $this->getValue('ngp_creator'); }
	
	public static function getOrCreate(array $data, &$created=false)
	{
		$created = false;
		if (!($post = self::getBy('ngp_nid', $data['id'])))
		{
			$section = NG_Section::getOrCreate($data['postSection']);
			$post = self::blank(array(
				'ngp_nid' => $data['id'],
				'ngp_section' => $section->getID(),
				'ngp_title' => $data['title'],
			))->insert();
			$created = true;
			Logger::logCron("Created a new post in {$section->getTitle()}");
		}
		return $post;
	}
}
