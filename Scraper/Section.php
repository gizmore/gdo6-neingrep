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
	/**
	 * Scrape a section from front and from old back pointer.
	 * Old strategy which did not use fresh as only front pointer.
	 * You can re-enable this old strategy viy cfg_ng_scrape_fresh_only=false.
	 * 
	 * @param NG_Section $section
	 * @deprecated
	 */
	public function scrapeSection(NG_Section $section)
	{
		$this->scrapeSectionFront($section);
		$this->scrapeSectionBack($section);
	}
	
	/**
	 * Scrape section from beginning / newest posts.
	 * @param NG_Section $section
	 */
	public function scrapeSectionFront(NG_Section $section)
	{
		$this->scrapeSectionB($section, true);
	}
	
	/**
	 * Scrape older posts of a section.
	 * @param NG_Section $section
	 */
	public function scrapeSectionBack(NG_Section $section)
	{
		$this->scrapeSectionB($section, false);
	}
	
	public function scrapeSectionB(NG_Section $section, bool $front, $subsection='fresh')
	{
		$column = $front ? 'ngs_cursor_front' : 'ngs_cursor_back';
		$cursor = $section->getVar($column);
		
		# Back cursor and finished
		if ( (!$front) && ($section->isFinished()) )
		{
			return true;
		}
		
		$nextCursor = $cursor ? "?{$cursor}" : '';
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
// 				$section->delete(); # Better keep records :)
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
			$cursor = $crsr = $json['data']['nextCursor']; # cursor for reference, crsr may change to allow nice front/back- pointer handling.
		}
		elseif (!$cursor) # last page
		{
			$section->saveVar('ngs_scrape_finished', '1');
		}
		
		foreach ($posts as $data)
		{
			$created = $worthy = false;
			
			if ($post = NG_Post::getOrCreate($data, $created))
			{
				$post->saveVars(array(
					'ngp_comments' => $data['commentsCount'],
					'ngp_upvotes' => $data['upVoteCount'],
					'ngp_downvotes' => $data['downVoteCount'],
					'ngp_created' => Time::getDate($data['creationTs']),
				), true, $worthy);
				
				if ($front && (!$created))
				{
					Logger::logCron("Section front cursor nulled as we reached a known post.");
					$crsr = null;
				}
			}
		}
		
		# If we are a front request and have no back cursor yet =>
		# Make the back cursor our result.
		if ($front && (!$section->getVar('ngs_cursor_back')))
		{
			if (!$section->isFinished())
			{
				$section->saveVars(array(
					'ngs_cursor_back' => $cursor, # back cursor is page 2
				));
				$crsr = null; # front restarts fresh
			}
		}
		
		$section->saveVars(array(
			$column => $crsr,
			'ngs_scraped' => Time::getDate(),
		));
	}
}
