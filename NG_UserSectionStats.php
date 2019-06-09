<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_Object;
use GDO\DB\GDT_UInt;

final class NG_UserSectionStats extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_Object::make('nguss_section')->table(NG_User::table())->notNull()->primary()->cascade(),
			GDT_Object::make('nguss_user')->table(NG_User::table())->notNull()->primary()->cascade(),
			GDT_UInt::make('nguss_posts')->notNull()->initial('0'),
			GDT_UInt::make('nguss_comments')->notNull()->initial('0'),
		);
	}
	
}
