<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\Core\Logger;
use GDO\DB\GDT_AutoInc;
use GDO\User\GDT_User;
use GDO\DB\GDT_String;
use GDO\Address\GDT_Address;
use GDO\DB\GDT_UInt;
use GDO\Date\GDT_DateTime;
use GDO\DB\GDT_CreatedAt;
use GDO\DB\GDT_CreatedBy;
use GDO\User\GDO_User;

final class NG_User extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_AutoInc::make('ngu_id'),
			GDT_User::make('ngu_user'),
			GDT_String::make('ngu_name')->notNull()->caseI(),
			GDT_Address::make('ngu_address'),
			GDT_DateTime::make('ngu_last_active'), # last time active on 9gag
			GDT_UInt::make('ngu_posts')->notNull()->initial('0'), # total num posts
			GDT_UInt::make('ngu_ups')->notNull()->initial('0'), # total num ups (for posts)
			GDT_UInt::make('ngu_downs')->notNull()->initial('0'), # total num downs (for posts)
			GDT_UInt::make('ngu_comments')->notNull()->initial('0'), # total num comments
			GDT_UInt::make('ngu_likes')->notNull()->initial('0'), # total num likes (for comments)
			GDT_UInt::make('ngu_dislikes')->initial('0'), # total num dislikes (for comments)
			GDT_String::make('ngu_cursor_posts_front')->ascii()->caseS(),
			GDT_String::make('ngu_cursor_posts_back')->ascii()->caseS(),
			GDT_String::make('ngu_cursor_comment_posts_front')->ascii()->caseS(),
			GDT_String::make('ngu_cursor_comment_posts_back')->ascii()->caseS(),
			GDT_DateTime::make('ngu_scraped'), # last time scraped
			GDT_CreatedAt::make('ngu_created'),
			GDT_CreatedBy::make('ngu_creator'),
		);
	}
	
	public function getName() { return $this->getVar('ngu_name'); }
	public function displayName() { return html($this->getVar('ngu_name')); }
	
	public function renderCell() { return $this->displayName(); }
	
	public static function getOrCreate(array $data)
	{
		$username = $data['username'];
		if (!($user = self::getBy('ngu_name', $username)))
		{
			Logger::logCron("New NG_User $username");
			$user = self::blank(array(
				'ngu_name' => $username,
				'ngu_creator' => GDO_User::system()->getID(),
			))->insert();
		}
		return $user;
	}
}
