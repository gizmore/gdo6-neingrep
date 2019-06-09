<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_Object;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;

final class NG_PostLike extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_Object::make('ngpl_user')->table(NG_User::table())->primary()->cascade(),
			GDT_Object::make('ngpl_post')->table(NG_Post::table())->primary()->cascade(),
			GDT_DateTime::make('ngl_liked'),
		);
	}
	
	public static function like(NG_User $user, NG_Post $post)
	{
		if (!(self::getById($user->getID(), $post->getID())))
		{
			self::blank(array(
				'ngpl_user' => $user->getID(),
				'ngpl_post' => $post->getID(),
				'ngpl_liked' => Time::getDate(),
			))->insert();
			return true;
		}
		return false;;
	}
}