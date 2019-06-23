<?php
namespace GDO\NeinGrep\Method;

use GDO\Core\Method;
use GDO\NeinGrep\Module_NeinGrep;
use GDO\NeinGrep\NG_Comment;
use GDO\NeinGrep\NG_Post;
use GDO\NeinGrep\NG_User;

final class Stats extends Method
{
	public function execute()
	{
		$tVars = array(
			'requests' => number_format(Module_NeinGrep::instance()->cfgRequestCount()),
			'users' => number_format(NG_User::table()->countWhere()),
			'posts' => number_format(NG_Post::table()->countWhere()),
			'urgent' => number_format(NG_Post::table()->countWhere("ngp_urgent")),
			'known' => number_format(NG_Post::table()->countWhere('ngp_creator IS NOT NULL')),
			'revealed' => number_format(NG_Post::table()->countWhere('ngp_revealed IS NOT NULL')),
			'partial' => number_format(NG_Post::table()->countWhere('ngp_uid IS NOT NULL')),
			'ups' => number_format(NG_Post::table()->select('SUM(ngp_upvotes)')->exec()->fetchValue()),
			'comments' => number_format(NG_Comment::table()->countWhere()),
			'totalcomments' => number_format(NG_Post::table()->select('SUM(ngp_comments)')->exec()->fetchValue()),
			'likes' => number_format(NG_Comment::table()->select('SUM(ngc_likes)')->exec()->fetchValue()),
		);
		return $this->templatePHP('page/stats.php', $tVars);
	}
}
