<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_Object;

final class NG_CommentLike extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_Object::make('ngcl_user')->table(NG_User::table())->primary()->cascade(),
			GDT_Object::make('ngcl_comment')->table(NG_Comment::table())->primary()->cascade(),
		);
	}
}