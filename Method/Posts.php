<?php
namespace GDO\NeinGrep\Method;

use GDO\Table\MethodQueryTable;
use GDO\NeinGrep\NG_Post;
use GDO\UI\GDT_EditButton;
use GDO\NeinGrep\NGT_Post;
use GDO\NeinGrep\NGT_User;
use GDO\NeinGrep\NGT_Section;

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
			NGT_User::make('ngu_name'),
			NGT_Section::make('ngs_name'),
			$table->gdoColumn('ngp_created'),
			$table->gdoColumn('ngp_upvotes'),
			$table->gdoColumn('ngp_comments'),
			$table->gdoColumn('ngp_downvotes'),
			$table->gdoColumn('ngp_title'),
		);
	}
	
	public function getQuery()
	{
		return NG_Post::table()->select()->joinObject('ngp_creator', 'LEFT JOIN')->joinObject('ngp_section');
	}
}
