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
use GDO\Net\GDT_Url;
use GDO\Maps\GDT_Position;

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
			GDT_String::make('ngp_uid')->ascii()->caseS()->max(24),
			GDT_String::make('ngp_title')->binary()->caseS()->max(1024),
			GDT_Checkbox::make('ngp_urgent')->initial('0'),
			GDT_DateTime::make('ngp_created'),
			GDT_DateTime::make('ngp_revealed'),
			GDT_DateTime::make('ngp_scraped'),
			GDT_Checkbox::make('ngp_nsfw'),
			GDT_UInt::make('ngp_comments')->notNull()->initial('0'),
			GDT_UInt::make('ngp_upvotes')->notNull()->initial('0'),
			GDT_UInt::make('ngp_downvotes')->notNull()->initial('0'),
			GDT_String::make('ngp_comment_ref')->max(64)->ascii()->caseS(),
			GDT_Url::make('ngp_image'),
			GDT_Position::make('ngp_position'),
			GDT_DateTime::make('ngp_image_scanned'),
		);
	}
	
	public function getPostID() { return $this->getVar('ngp_nid'); }
	public function getTitle() { return $this->getVar('ngp_title'); }
	public function getCommentRef() { return $this->getVar('ngp_comment_ref'); }
	public function getCommentCount() { return $this->getValue('ngp_comments'); }
	public function displayTitle() { return $this->display('ngp_title'); }
	
	public function hrefGag() { return "http://9gag.com/gag/{$this->getPostID()}"; }
	public function hrefImage() { return $this->getVar('ngp_image'); }
	
	/**
	 * @return NG_User
	 */
	public function getUser() { return $this->getValue('ngp_creator'); }
	
	/**
	 * @return NG_Section
	 */
	public function getSection() { return $this->getValue('ngp_section'); }
	
	/**
	 * Check if a user commented a post
	 * @param NG_User $user
	 * @return boolean
	 */
	public function hasCommented(NG_User $user)
	{
		return NG_Comment::table()->countWhere("ngc_user = {$user->getID()} AND ngc_post = {$this->getID()}") > 0;
	}
	
	/**
	 * 
	 * @param array $data
	 * @param boolean $created
	 * @return \GDO\NeinGrep\NG_Post
	 */
	public static function getOrCreate(array $data, &$created=false)
	{
		$created = false;
		if (!($post = self::getBy('ngp_nid', $data['id'])))
		{
			if ($section = NG_Section::getOrCreate($data['postSection']))
			{
				$post = self::blank(array(
					'ngp_nid' => $data['id'],
					'ngp_section' => $section->getID(),
					'ngp_title' => html_entity_decode($data['title'], ENT_QUOTES|ENT_HTML5),
					'ngp_nsfw' => $data['nsfw']?'1':'0',
				));
				
				if (@$data['images']['image700'])
				{
					$post->setVar('ngp_image', $data['images']['image700']['url']);
				}
				
				$post->insert();
				$created = true;
				Logger::logCron("New NG_Post in {$section->getTitle()}");
			}
		}
		return $post;
	}
}
