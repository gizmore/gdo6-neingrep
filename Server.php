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

/**
 * Simple endless loop of scraping stuff.
 * Currently there are 3 Scrapers: Post, User, Section.
 * @author gizmore
 */
final class Server
{
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
		NG_User::getOrCreate(['username' => 'zoidberg11917']); # initial fix
		NG_Section::getOrCreateSection('default', 'Hot');
		
		$this->recalculateStats();
		
		Warmup::make()->scrapeWarmup();
		
		while (true)
		{
			$this->scrapeNextSection();
			$this->scrapeNextPost();
			$this->scrapeNextUser();
			
			$this->recalculateStats();

			Logger::logCron("Next cycle!");
			sleep(1);
		}
	}
	
	public function scrapeUserTimeout()
	{
		return 120;
	}
	
	public function scrapeNextUser()
	{
		$query = NG_User::table()->select();
		$cut = Time::getDate(time() - $this->scrapeUserTimeout());
		$query->where("ngu_scraped IS NULL OR ngu_scraped<'$cut'");
		$query->order("RAND()");
		$query->order('ngu_scraped');
		$query->order("IF(ngu_creator={$this->system}, 1, 0)");
		$query->first();
		if ($user = $query->exec()->fetchObject())
		{
			return User::make()->scrapeUser($user);
		}
	}
	
	public function scrapeSectionTimeout()
	{
		return 120;
	}
	
	public function scrapeNextSection()
	{
		$query = NG_Section::table()->select();
		$query->order('ngs_scraped');
		$cut = Time::getDate(time() - $this->scrapeSectionTimeout());
		$query->where("ngs_scraped IS NULL OR ngs_scraped < '$cut'");
		$banned_sections = join(', ', Module_NeinGrep::instance()->cfgBannedSections());
		$query->where("ngs_id NOT IN ($banned_sections)");
		$query->first();
		if ($section = $query->exec()->fetchObject())
		{
			Section::make()->scrapeSection($section);
		}
	}
	
	public function scrapePostTimeout()
	{
		return 120;
	}
	
	public function scrapeNextPost()
	{
		Logger::logCron("Scraping next post.");
		$query = NG_Post::table()->select();
		$cut = Time::getDate(time()-$this->scrapePostTimeout());
		$query->where("ngp_scraped IS NULL OR ngp_scraped<'$cut'");
		$query->order("RAND()");
		$query->first();
		if ($post = $query->exec()->fetchObject())
		{
			return Post::make()->scrapePost($post);
		}
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
}
