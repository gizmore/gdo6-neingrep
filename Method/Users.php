<?php
namespace GDO\NeinGrep\Method;

use GDO\Table\MethodQueryTable;
use GDO\NeinGrep\NG_Post;
use GDO\UI\GDT_EditButton;
use GDO\NeinGrep\NGT_Post;
use GDO\NeinGrep\NGT_User;
use GDO\NeinGrep\NG_User;
use GDO\DB\GDT_Id;

/**
 * List scraped 9gag posts.
 * Default sorting is score desc.
 * @author gizmore
 */
final class Users extends MethodQueryTable
{
	public function getHeaders()
	{
		$table = NG_User::table();
		return array(
			NGT_User::make('ngu_name'),
			$table->gdoColumn('ngu_posts'),
			$table->gdoColumn('ngu_ups'),
			$table->gdoColumn('ngu_downs'),
			$table->gdoColumn('ngu_comments'),
			$table->gdoColumn('ngu_likes'),
			$table->gdoColumn('ngu_dislikes'),
			$table->gdoColumn('ngu_last_active'),
		);
	}
	
	public function getQuery()
	{
		return NG_User::table()->select();
	}
}
