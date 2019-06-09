<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_AutoInc;
use GDO\DB\GDT_Object;
use GDO\UI\GDT_Message;
use GDO\DB\GDT_UInt;
use GDO\DB\GDT_String;
use GDO\Date\GDT_DateTime;
use GDO\Core\Logger;

final class NG_Comment extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_AutoInc::make('ngc_id'),
			GDT_String::make('ngc_cid')->ascii()->notNull()->unique(),
			GDT_Object::make('ngc_user')->table(NG_User::table())->index()->notNull()->cascade(),
			GDT_Object::make('ngc_post')->table(NG_Post::table())->index()->notNull()->cascade(),
			GDT_Message::make('ngc_message')->binary()->caseS(),
			GDT_DateTime::make('ngc_created'),
			GDT_UInt::make('ngc_likes')->initial('0'),
			GDT_UInt::make('ngc_dislikes')->initial('0'),
		);
	}
	
	public static function getOrCreate(array $comment)
	{
		
	}
	
	public static function createComment(array $comment)
	{
		Logger::logCron("Created new comment.");
		
	}
}