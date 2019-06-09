<?php
namespace GDO\NeinGrep\Scraper;

use GDO\Core\Logger;
use GDO\Date\Time;
use GDO\NeinGrep\NG_Post;
use GDO\NeinGrep\NG_Section;
use GDO\NeinGrep\Scraper;
use GDO\Net\HTTP;

/**
 * Scrape one page of a section like default/hot or german/fresh.
 * @author gizmore
 */
final class Section extends Scraper
{
	public function scrapeSection(NG_Section $section)
	{
		$nextCursor = $section->getVar('ngs_cursor');
		Logger::logCron("Scraping section {$section->getTitle()}... via {$section->getName()} {$nextCursor}");
		
		$url = $this->apiURL() . "group-posts/group/{$section->getName()}/type/fresh{$nextCursor}";
		
		$response = HTTP::getFromURL($url, false, false, $this->httpHeaders());
		
		$json = json_decode($response, true);
		
		print_r($json);
		$this->sleep(); # sleep a while to not get detected by evil devops
		
		$posts = $json['data']['posts'];
		
		$changed = false;
		foreach ($posts as $data)
		{
			$created = $worthy = false;
			$post = NG_Post::getOrCreate($data, $created);
			$post->saveVars(array(
				'ngp_nsfw' => $data['nsfw'],
				'ngp_comments' => $data['commentsCount'],
				'ngp_upvotes' => $data['upVoteCount'],
				'ngp_downvotes' => $data['downVoteCount'],
				'ngp_creator' => null,
			), true, $worthy);
			
			$changed = $worthy || $created;
		}
		
		$nextCursor = $changed ? $json['data']['nextCursor'] : null;
		$section->saveVars(array(
			'ngs_cursor' => $nextCursor,
			'ngs_scraped' => Time::getDate(),
		));
	}
}
