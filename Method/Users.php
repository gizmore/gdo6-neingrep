<?php
namespace GDO\NeinGrep\Method;

use GDO\Table\MethodQueryTable;
use GDO\NeinGrep\NG_Post;
use GDO\UI\GDT_EditButton;
use GDO\NeinGrep\NGT_Post;
use GDO\NeinGrep\NGT_User;
use GDO\NeinGrep\NG_User;

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
			NGT_User::make(),
			$table->gdoColumn('ngu_last_active'),
			$table->gdoColumn('ngp_comments'),
			$table->gdoColumn('ngp_downvotes'),
			$table->gdoColumn('ngp_title'),
		);
	}
	
	public function getQuery()
	{
		return NG_Post::table()->select();
	}
}
