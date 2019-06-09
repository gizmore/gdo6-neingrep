<?php
namespace GDO\NeinGrep\Scraper;

use GDO\Core\Logger;
use GDO\Date\Time;
use GDO\NeinGrep\NG_Post;
use GDO\NeinGrep\NG_PostCommented;
use GDO\NeinGrep\Scraper;
use GDO\Net\HTTP;
use GDO\NeinGrep\NG_User;

/**
 * Scrape the newest posts from a user and posts where he commented.
 * @author gizmore
 */
final class User extends Scraper
{
	public function scrapeUser(NG_User $user)
	{
		$this->scrapeUserPosts($user, true);
		$this->scrapeUserPosts($user, false);

		$this->scrapeUserCommentsPosts($user, true);
		$this->scrapeUserCommentsPosts($user, false);
		
		$user->saveVars(array(
			'ngu_scraped' => Time::getDate(),
		));
	}

	/**
	 * Scrape all posts by a user.
	 * @param NG_User $user
	 * @param string $nextCursor
	 * @return boolean
	 */
	public function scrapeUserPosts(NG_User $user, bool $front)
	{
		$column = $front ? 'ngu_cursor_posts_front' : 'ngu_cursor_posts_back';
		$cursor = $user->getVar($column);
		
		# Do not let front run when back is null
		if ($front && ($user->getVar('ngu_cursor_posts_back')===null))
		{
			return true;
		}
		
		Logger::logCron("Scraping {$user->getName()} op posts {$cursor}");
		
		$url = $this->apiURL() . "user-posts/username/{$user->getName()}/type/posts";
		$url .= $cursor ? "?$cursor" : '';
		
		$this->beforeRequest(); # sleep a while to not get detected by evil devops
		$response = HTTP::post($url, [], false, $this->httpHeaders());
		$json = json_decode($response, true);

		$posts = $json['data']['posts'];
		$nPosts = count($posts);
		Logger::logCron("Got {$nPosts} posts.");
		$this->sleep(); # sleep a while to not get detected by evil devops
		
		$cursor = $nPosts ? $json['data']['nextCursor'] : null;
		foreach ($posts as $data)
		{
			$created = $worthy = false;

			$post = NG_Post::getOrCreate($data, $created);
			
			if ($front && (!$created))
			{
				$cursor = null;
			}

			$post->saveVars(array(
				'ngp_comments' => $data['commentsCount'],
				'ngp_upvotes' => $data['upVoteCount'],
				'ngp_downvotes' => $data['downVoteCount'],
				'ngp_creator' => $user->getID(),
				'ngp_created' => Time::getDate($data['creationTs']),
				'ngp_urgent' => '0',
			), true, $worthy);
		}
		
		$user->saveVar($column, $cursor);
	}
	
	/**
	 * Scrape all posts a user has commented.
	 * Mark it as commented.
	 * @param NG_User $user
	 * @param string $nextCursor
	 */
	public function scrapeUserCommentsPosts(NG_User $user, bool $front)
	{
		$column = $front ? 'ngu_cursor_comment_posts_front' : 'ngu_cursor_comment_posts_back';
		$cursor = $user->getVar($column);
		# Do not let front run when back is null
		if ($front && ($user->getVar('ngu_cursor_comment_posts_back')===null))
		{
			return true;
		}
		
		Logger::logCron("Scraping {$user->getName()} comment posts {$cursor}");

		$url = $this->apiURL() . "user-posts/username/{$user->getName()}/type/comments";
		$url .= $cursor ? "?$cursor" : '';
		$this->beforeRequest(); # sleep a while to not get detected by evil devops
		$response = HTTP::post($url, [], false, $this->httpHeaders());

		$json = json_decode($response, true);
		$posts = $json['data']['posts'];
		$nPosts = count($posts);
		Logger::logCron("Got {$nPosts} Posts.");
		$this->sleep(); # sleep a while to not get detected by evil devops
		
		$cursor = $nPosts ? $json['data']['nextCursor'] : null;
		foreach ($posts as $data)
		{
			$created = $worthy = false;
			$post = NG_Post::getOrCreate($data, $created);
			
			if ($front && (!$created))
			{
				$cursor = null;
			}
			
			# Mark as commented
			$created = NG_PostCommented::commented($user, $post);
			
			$post->saveVars(array(
				'ngp_comments' => $data['commentsCount'],
				'ngp_upvotes' => $data['upVoteCount'],
				'ngp_downvotes' => $data['downVoteCount'],
				'ngp_created' => Time::getDate($data['creationTs']),
			), true, $worthy);
		}
		
		$user->saveVar($column, $cursor);
	}

	### THIS API IS ONLY FOR YOURSELF (PRIVATE) - You cannot scrape it
// 	public function scrapeUserLikesURL(NG_User $user)
// 	{
// 		return "https://9gag.com/v1/user-posts/username/{$user->getName()}/type/likes";
// 	}
// 	public function scrapeUserLikes(NG_User $user, string $nextCursor='')
// 	{
// 		$url = $this->scrapeUserLikesURL($user) . $nextCursor;
// 		print_r($url,"\n");
// 		$response = HTTP::post($url, [], false, $this->httpHeaders());
// 		$this->sleep();
// 		$json = json_decode($response, true);
// 		print_r($json);
// 		die();
// 		$posts = $json['data']['posts'];
// 		foreach ($posts as $data)
// 		{
// 			$post = NG_Post::getOrCreate($data);
// 			$post->saveVars(array(
// 				'ngp_nsfw' => $data['nsfw'],
// 				'ngp_comments' => $data['commentsCount'],
// 				'ngp_upvotes' => $data['upVoteCount'],
// 				'ngp_downvotes' => $data['downVoteCount'],
// 			));
// 			if (!NG_PostLike::like($user, $post))
// 			{
// 				return true; # all done
// 			}
// 		}
// 		return $this->scrapeUserLikes($user, $data['data']['nextCursor']);
// 	}
}
