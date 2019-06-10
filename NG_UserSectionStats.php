<?php
namespace GDO\NeinGrep;

use GDO\Core\GDO;
use GDO\DB\GDT_Object;
use GDO\DB\GDT_UInt;

/**
 * Statistics by section.
 * @author gizmore
 */
final class NG_UserSectionStats extends GDO
{
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_Object::make('nguss_section')->table(NG_User::table())->notNull()->primary()->cascade()->index(),
			GDT_Object::make('nguss_user')->table(NG_User::table())->notNull()->primary()->cascade(),
			GDT_UInt::make('nguss_posts')->notNull()->initial('0'),
			GDT_UInt::make('nguss_ups')->notNull()->initial('0'),
			GDT_UInt::make('nguss_downs')->notNull()->initial('0'),
			GDT_UInt::make('nguss_comments')->notNull()->initial('0'),
			GDT_UInt::make('nguss_likes')->notNull()->initial('0'),
			GDT_UInt::make('nguss_dislikes')->notNull()->initial('0'),
		);
	}
	
	/**
	 * @return NG_User
	 */
	public function getUser() { return $this->getValue('nguss_user'); }
	
	##############
	### Static ###
	##############
	public static function updateStatistics(NG_Section $section, NG_User $user)
	{
		$stat = self::getOrCreate($section, $user);
		$query = $stat->updateQuery();
		$query->set("nguss_posts = ( SELECT COUNT(*) FROM ng_post WHERE ngp_section = nguss_section AND ngp_creator = nguss_user )");
		$query->set("nguss_ups = IFNULL( (SELECT SUM(ngp_upvotes) FROM ng_post WHERE ngp_section = nguss_section AND ngp_creator = nguss_user), 0 )");
		$query->set("nguss_downs = IFNULL( (SELECT SUM(ngp_downvotes) FROM ng_post WHERE ngp_section = nguss_section AND ngp_creator = nguss_user), 0 )");
		$query->set("nguss_comments = ( SELECT COUNT(*) FROM ng_comment JOIN ng_post ON ngc_post = ngp_id WHERE ngp_section = nguss_section AND ngc_user = nguss_user )");
		$query->set("nguss_likes = IFNULL( (SELECT SUM(ngc_likes) FROM ng_comment JOIN ng_post ON ngc_post = ngp_id WHERE ngp_section = nguss_section AND ngc_user = nguss_user), 0 )");
		$query->set("nguss_dislikes = IFNULL( (SELECT SUM(ngc_dislikes) FROM ng_comment JOIN ng_post ON ngc_post = ngp_id WHERE ngp_section = nguss_section AND ngc_user = nguss_user), 0 )");
		$query->exec();
	}
	
	public static function getOrCreate(NG_Section $section, NG_User $user)
	{
		if (!($stat = self::getById($section->getID(), $user->getID())))
		{
			$stat = self::blank(array(
				'nguss_section' => $section->getID(),
				'nguss_user' => $user->getID(),
				'nguss_posts' => '0',
				'nguss_ups' => '0',
				'nguss_downs' => '0',
				'nguss_comments' => '0',
				'nguss_likes' => '0',
				'nguss_dislikes' => '0',
			))->insert();
		}
		return $stat;
	}
	
}
