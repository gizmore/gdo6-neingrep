<?php
namespace GDO\NeinGrep;

use GDO\User\GDO_User;
use GDO\Date\Time;
use GDO\Core\Logger;
use GDO\NeinGrep\Scraper\Section;
use GDO\NeinGrep\Scraper\Warmup;
use GDO\NeinGrep\Scraper\User;
use GDO\NeinGrep\Scraper\Post;

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

		Warmup::make()->scrapeWarmup();
		
		while (true)
		{
			$this->scrapeNextSection();
			$this->scrapeNextPost();
			$this->scrapeNextUser();
			
			Logger::logCron("Recalculating stats.");
			sleep(1);
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
		$query->where("ngu_scraped<'$cut'");
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
	
	public function recalculateStats()
	{
		
	}
}
