<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_Object;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;
use GDO\Core\Logger;

final class NG_PostCommented extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_Object::make('ngpc_user')->table(NG_User::table())->primary()->cascade(),
			GDT_Object::make('ngpc_post')->table(NG_Post::table())->primary()->cascade(),
			GDT_DateTime::make('ngpc_commented'),
		);
	}
	
	public static function commented(NG_User $user, NG_Post $post, $time=0)
	{
		if (!(self::getById($user->getID(), $post->getID())))
		{
			Logger::logCron("A user commented on a post");
			self::blank(array(
				'ngpc_user' => $user->getID(),
				'ngpc_post' => $post->getID(),
				'ngpc_commented' => Time::getDate($time?$time:time()),
			))->insert();
			return true;
		}
		return false;;
	}
}