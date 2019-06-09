<?php
namespace GDO\NeinGrep\Method;

use GDO\Table\MethodQueryTable;
use GDO\NeinGrep\NG_Post;
use GDO\UI\GDT_EditButton;
use GDO\NeinGrep\NGT_Post;
use GDO\NeinGrep\NGT_User;

/**
 * List scraped 9gag posts.
 * Default sorting is score desc.
 * @author gizmore
 */
final class Posts extends MethodQueryTable
{
	public function getHeaders()
	{
		$table = NG_Post::table();
		return array(
			NGT_Post::make(),
			NGT_User::make(),
			$table->gdoColumn('ngp_upvotes'),
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
