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
		$this->scrapeSectionB($section, true);
		$this->scrapeSectionB($section, false);
	}

	public function scrapeSectionB(NG_Section $section, bool $front)
	{
		$column = $front ? 'ngs_cursor_front' : 'ngs_cursor_back';
		$cursor = $section->getVar($column);
		
		if ($front && ($section->getVar('ngs_cursor_back') === null))
		{
			return true;
		}
		
		$nextCursor = $cursor ? "?{$cursor}" : '';
		$subsection = 'fresh';
		Logger::logCron("Scraping section {$section->getTitle()}... via {$section->getName()} {$subsection} {$nextCursor}");

		$this->beforeRequest();
		$url = $this->apiURL() . "group-posts/group/{$section->getName()}/type/{$subsection}{$nextCursor}";
		$response = HTTP::getFromURL($url, false, false, $this->httpHeaders());
		
		$json = json_decode($response, true);
		
		if ($json['meta']['status'] === 'Failure')
		{
			if ($json['meta']['errorMessage'] === 'Invalid group')
			{
				Logger::logCron("deleting section {$section->getTitle()}");
				$section->delete();
				return false;
			}
		}
		if (!($posts = @$json['data']['posts']))
		{
			$section->saveVars(array(
				'ngs_scraped' => Time::getDate(),
			));
			Logger::logCron("Error\n" . print_r($response, 1));
			return false;
		}

		$nPosts = count($posts);
		
		if (!$nPosts)
		{
			$section->saveVars(array(
				$column => null,
			));
			return true;
		}
		
// 		print_r($json);
		Logger::logCron("Got {$nPosts} posts.");
		$this->sleep(); # sleep a while to not get detected by evil devops
		
		if (@$json['data']['nextCursor'])
		{
			$cursor = $json['data']['nextCursor'];
		}
		
		foreach ($posts as $data)
		{
			$created = $worthy = false;
			$post = NG_Post::getOrCreate($data, $created);
			
			$post->saveVars(array(
				'ngp_comments' => $data['commentsCount'],
				'ngp_upvotes' => $data['upVoteCount'],
				'ngp_downvotes' => $data['downVoteCount'],
				'ngp_created' => Time::getDate($data['creationTs']),
			), true, $worthy);
			
			if ($front && (!$created))
			{
				Logger::logCron("Section front cursor nulled.");
				$cursor = null;
			}
		}
		
		$section->saveVars(array(
			$column => $cursor,
			'ngs_scraped' => Time::getDate(),
		));
	}
}
