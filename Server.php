<?php
namespace GDO\NeinGrep;

use GDO\User\GDO_User;
use GDO\Date\Time;
use GDO\Core\Logger;
use GDO\NeinGrep\Scraper\Section;
use GDO\NeinGrep\Scraper\Warmup;
use GDO\NeinGrep\Scraper\User;
use GDO\NeinGrep\Scraper\Post;
use GDO\DB\Database;
use GDO\NeinGrep\Scraper\Geotag;

/**
 * Simple endless loop of scraping stuff.
 * Currently there are 3 Scrapers: Post, User, Section.
 * @author gizmore
 */
final class Server
{
	private $hot; # The default section. I name it hot even tho we look in fresh.
	
	public static function make(int $argc, array $argv)
	{
		$instance = new self($argc, $argv);
		return $instance;
	}
	
	public function __construct(int $argc, array $argv)
	{
		$this->argc = $argc;
		$this->argv = $argv;
	}
	
	private $argc;
	private $argv;
	
	private $system;
	private $module;
	
	public function init()
	{
		$this->system = GDO_User::system()->getID();
		$this->module = Module_NeinGrep::instance();
		return $this;
	}
	
	public function run()
	{
		# Create first section
		$this->hot = NG_Section::getOrCreateSection('default', 'Hot');
		
		if (in_array('--fix-stats', $this->argv, true))
		{
			$this->recalculateSectionStats();
			die();
		}
		
		$this->recalculateStats();
		
		Warmup::make()->scrapeWarmup();
		
		while (true)
		{
			$this->showStatistics();
			
			$section = $this->scrapeNextSection();
			$this->scrapeNextPost($section); # Scrape a next random post.
			$this->scrapeNextUser(); # scrape user posts and posts where he commented
// 			$this->scrapeNextGeotag(); # not working
			$this->scrapeRevealEasy(); # easy revealer for awaiting reveal
			
			$this->recalculateStats();

			Logger::logCron("Next cycle!");
			sleep(1);
		}
	}
	
	public function showStatistics()
	{
		$requests = number_format(Module_NeinGrep::instance()->cfgRequestCount());
		$users = number_format(NG_User::table()->countWhere());
		$posts = number_format(NG_Post::table()->countWhere());
		$urgent = number_format(NG_Post::table()->countWhere("ngp_urgent"));
		$known = number_format(NG_Post::table()->countWhere('ngp_creator IS NOT NULL'));
		$revealed = number_format(NG_Post::table()->countWhere('ngp_revealed IS NOT NULL'));
		$partial = number_format(NG_Post::table()->countWhere('ngp_uid IS NOT NULL'));
		$ups = number_format(NG_Post::table()->select('SUM(ngp_upvotes)')->exec()->fetchValue());
		$comments = number_format(NG_Comment::table()->countWhere());
		$totalcomments = number_format(NG_Post::table()->select('SUM(ngp_comments)')->exec()->fetchValue());
		$likes = number_format(NG_Comment::table()->select('SUM(ngc_likes)')->exec()->fetchValue());
		
		Logger::logCron("$requests Requests. $users User.");
		Logger::logCron("$posts posts - $known OPs identified - $partial OPs partially - $revealed OPs revealed. $urgent urgent posts queued.");
		Logger::logCron("$ups ups. $comments comments of $totalcomments loaded. $likes likes.");
	}
	
	public function scrapeUserTimeout()
	{
		return 900;
	}
	
	public function scrapeNextUser()
	{
		$query = NG_User::table()->select('*');

		# Scrape cut by time
		$cut = Time::getDate(time() - $this->scrapeUserTimeout());
		$query->where("ngu_scraped IS NULL OR ngu_scraped<'$cut'");

		# Scored order
		$query->select("IF(ngu_urgent=1 AND (ngu_scrape_finished_posts=0 OR ngu_scrape_finished_comments=0) , 5000, 0) urgent_score");
		$query->select("IF(ngu_creator={$this->system}, 0, 100) system_score"); # basescore for user created users
		$query->select("IF(ngu_scrape_finished_posts=0 OR ngu_scrape_finished_comments=0, 0, 500) finish_score"); # unfinished users
		$query->orderDESC("RAND() * (urgent_score + system_score + finish_score)");
		
		$query->first();

		if ($user = $query->exec()->fetchObject())
		{
			return User::make()->scrapeUser($user);
		}
	}
	
	public function scrapeSectionTimeout()
	{
		return 600;
	}
	
	/**
	 * Get the next random section to scrape.
	 * @param bool $front
	 * @return \GDO\NeinGrep\NG_Section
	 */
	public function getNextRandomSection(bool $front)
	{
		if ($front && Module_NeinGrep::instance()->cfgOnlyFresh())
		{
			return $this->hot;
		}
		
		$query = NG_Section::table()->select();
		$query->order('RAND()');
		
		# Filter scraped
		$cut = Time::getDate(time() - $this->scrapeSectionTimeout());
		$query->where("ngs_scraped IS NULL OR ngs_scraped < '$cut'");
		
		# Filter sections
		if ($banned_sections = join(', ', Module_NeinGrep::instance()->cfgBannedSections()))
		{
			$query->where("ngs_id NOT IN ($banned_sections)");
		}
		
		# Filter sections
		if ($allowed_sections = join(', ', Module_NeinGrep::instance()->cfgAllowedSections()))
		{
			$query->where("ngs_id IN ($allowed_sections)");
		}

		$query->first();
		return $query->exec()->fetchObject();
	}
	
	private $cycle = -1;
	
	/**
	 * Scrape a section from front and from backpointer (older posts).
	 */
	public function scrapeNextSection()
	{
		$this->cycle++;
		$fresh = Module_NeinGrep::instance()->cfgOnlyFresh();
		
		# Get both sections to scrape.
		$front = $this->getNextRandomSection(true);
		
		$back = $fresh ? $this->getNextRandomSection(false) : $front;
		
// 		$this->lastSection = $back; # remember current section for?
		
		# Scrape posts
		$scraper = Section::make();
		if ( ($fresh) && ((($this->cycle%2)===0)) )
		{
			$scraper->scrapeSectionFront($front);
		}
		$scraper->scrapeSectionBack($back);
		
		return $back;
	}
	
	public function scrapePostTimeout()
	{
		return 900;
	}
	
	/**
	 * Find next post by a scoring algo and call post scraper.
	 */
	public function scrapeNextPost(NG_Section $section=null)
	{
		Logger::logCron("Scraping next post.");
		$query = NG_Post::table()->select('*');
		$query->select('IF(ngp_urgent, 5000, 0) urgent_score'); # urgent posts are scored high
		$query->select('IF(ngp_uid IS NOT NULL AND ngp_creator IS NULL, 250, 0) reveal_score'); # a post with a possible reveal
		$query->select('IF(ngp_creator IS NULL, 100, 0) creator_score'); # unknown op
		$query->select('IF(ngp_scraped IS NULL, 50, 0) fresh_score');
		$query->select('LEAST(ngp_upvotes/5, 20) upvote_score');
		$query->select('LEAST(ngp_comments/5, 35) comment_score');
		$cut = Time::getDate(time()-$this->scrapePostTimeout());
		$query->where("ngp_scraped IS NULL OR ngp_scraped<'$cut'");
		if ($section)
		{
			$query->where("ngp_section={$section->getID()}");
		}
		else
		{
			# Filter sections
			if ($banned_sections = join(', ', Module_NeinGrep::instance()->cfgBannedSections()))
			{
				$query->where("ngp_section NOT IN ($banned_sections)");
			}
			
			# Filter sections
			if ($allowed_sections = join(', ', Module_NeinGrep::instance()->cfgAllowedSections()))
			{
				$query->where("ngp_section IN ($allowed_sections)");
			}
		}

		$query->orderDESC("RAND() * (urgent_score + reveal_score + creator_score + fresh_score + upvote_score + comment_score)");
		$query->first();

		if ($post = $query->exec()->fetchObject())
		{
			Post::make()->scrapePost($post);
		}
	}
	
	public function scrapeNextGeotag()
	{
		$table = NG_Post::table();
		$query = $table->select();
		$query->where("ngp_image IS NOT NULL");
		$query->where("ngp_image_scanned IS NULL");
		$query->joinObject("ngp_creator");
		$query->first();
		$query->order("RAND()");
		if ($post = $query->exec()->fetchObject())
		{
			Geotag::make()->scrapeGeotag($post);
		}
	}
	
	/**
	 * Assign meanwhile known OPs.
	 */
	public function scrapeRevealEasy()
	{
		$table = NG_Post::table();
		$query = $table->update();
		$query->set("ngp_uid = IF( (SELECT ngu_id FROM ng_user WHERE ngu_uid = ngp_uid), NULL, ngp_uid )");
		$query->set("ngp_creator = IFNULL ( (SELECT ngp_id FROM ng_user WHERE ngu_uid = ngp_uid), NULL )");
		$query->where("ngp_uid IS NOT NULL");
		$query->exec();
		$count = Database::instance()->affectedRows();
		Logger::logCron("Found $count hidden ops.");
	}
	
	/**
	 * Recalculate user summary statistics.
	 */
	public function recalculateStats()
	{
		Logger::logCron("Recalculating stats.");
		sleep(1);
		
		# User posts
		$subquery_postcount = "IFNULL( (SELECT COUNT(*) FROM ng_post WHERE ngp_creator = u.ngu_id), 0 )";
		$subquery_ups = "IFNULL( (SELECT SUM(ngp_upvotes) FROM ng_post WHERE ngp_creator = u.ngu_id), 0 )";
		$subquery_downs = "IFNULL( (SELECT SUM(ngp_downvotes) FROM ng_post WHERE ngp_creator = u.ngu_id), 0 )";
		# User comments
		$subquery_comments = "IFNULL( (SELECT COUNT(*) FROM ng_comment WHERE ngc_user = u.ngu_id), 0 )";
		$subquery_likes = "IFNULL( (SELECT SUM(ngc_likes) FROM ng_comment WHERE ngc_user = u.ngu_id), 0 )";
		$subquery_dislikes = "IFNULL( (SELECT SUM(ngc_dislikes) FROM ng_comment WHERE ngc_user = u.ngu_id), 0 )";
		# Update 1		
		$query = "UPDATE ng_user u SET ngu_posts = ( $subquery_postcount ), ngu_ups = ( $subquery_ups ), ngu_downs = ( $subquery_downs), ";
		$query .= "ngu_comments = ( $subquery_comments ), ngu_likes = ( $subquery_likes ), ngu_dislikes = ( $subquery_dislikes ) ";
		Database::instance()->queryWrite($query);
		
		# Section posts
		$subquery_postcount = "IFNULL( (SELECT COUNT(*) FROM ng_post WHERE ngp_section = s.ngs_id), 0 )";
		$query = "UPDATE ng_section s SET ngs_posts = ( $subquery_postcount)";
		Database::instance()->queryWrite($query);
	}
	
	public function recalculateSectionStats()
	{
		Logger::logCron("Recalculating all stats. Takes a while...");
		$users = NG_User::table();
		$result = $users->select()->exec();
		$count = 0;
		while ($user = $users->fetch($result))
		{
			$this->recalculateUserSectionStats($user);
			$count++;
			if ( ($count % 500) === 0 )
			{
				Logger::logCron("$count users done...");
			}
		}
	}
	
	public function recalculateUserSectionStats(NG_User $user)
	{
		foreach (NG_Section::table()->all() as $section)
		{
			if ($section->hasParticipated($user))
			{
				NG_UserSectionStats::updateStatistics($section, $user);
			}
		}
	}
}
