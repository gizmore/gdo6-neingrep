<?php
namespace GDO\NeinGrep\Scraper;

use GDO\NeinGrep\NG_Post;
use GDO\NeinGrep\Scraper;
use GDO\Core\Logger;
use GDO\Date\Time;
use GDO\Net\HTTP;
use GDO\NeinGrep\NG_User;
use GDO\NeinGrep\NG_PostCommented;
use GDO\NeinGrep\NG_Comment;

/**
 * Scrape the comments of a post to reveal new users.
 * @author gizmore
 */
final class Post extends Scraper
{
	public function commentURL() { return "https://comment-cdn.9gag.com/v1/cacheable/comment-list.json"; }
	
	public function scrapePost(NG_Post $post)
	{
		Logger::logCron("Scraping post {$post->getPostID()} {$post->displayTitle()}");
		$this->scrapePostComments($post);
		$post->saveVar('ngp_scraped', Time::getDate());
		
	}
	
	public function scrapePostCommentsPostData(NG_Post $post)
	{
		
	}
	
	public function scrapePostComments(NG_Post $post)
	{
		$ref = $post->getVar('ngp_comment_ref');
		$url = $this->commentURL();
		$postData = array(
			'appId' => 'a_dd8f2b7d304a10edaf6f29517ea0ca4100a43d1b',
			'url' => $post->hrefGag(),
			'count' => 10,
// 			'order' => 'date',
			'order' => 'score',
		);
		if ($ref)
		{
			$postData['ref'] = $ref;
		}
// 		$postData['auth'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NjAwNTU1ODgsIm5iZiI6MTU2MDA1NTI4OCwiZXhwIjoxNTYwMDk4Nzg4LCJwcml2YXRlIjoicDRRdXBXeCtsUGYwOUJCQW9WWG5QZz09LnFPZ0hGOVFacU5HSkYyWnRYNWpLTUFyaGdEenUxbG1aellzZDVJRTRneTluT1FJenFEVTFER3p4TmFMRWVqeU5qa1F3aFM5eDJGdW9IbkV5TVJFK3lNeFBNeW54NkNJWmthZkRYRWlsWUc1d0Q3TU9XVU9tSWlEaXl2dmtxNElrZkV1dGt0SkQ3WHpmdWx1K0VkYTNmcUZLdzE0SktuNVdsWWVSYWxrVW55YmFaQmk4UmJHeEc2WitPc3hXaXJpeEpsRzY1UUs1bm5LcmtBeXBEQng0djVjRlZiYVBMNWQ4eEdYcXRaY0h4dVdaTmxaeUltcFF3U2tXM0lycHFYenJBV0IzMHJQWGpWejVlQ0k4SnpwRXNoUXZWM0dcL0lKOEtuQ3FZekVMaGRGSFNzZVJEaTdIOEhqQmZtYTJBaGlSUWZiaFwvSE0ya2YwVXF2dFVvWDBObE9XMFJcL2lpY2w0QmxKMHNrcDJPMzNmQ0NjdG5KdjE0RXRYcjhKWjVTZXdJQ3lRT3RXK0ZJMEVXcTBFZEZZd0xmZ01LbllQSmU0QncwNVBSOENSUkd1OUpHb052dmUxaEd0SVkwXC82ejd0ZmNlIn0.cCgVmffgoihzKdBDh6y07RkeFzFtKIulaAMfhUECCeA';
		$postData['origin'] = "https://9gag.com";
		Logger::logCron("Scraping Post comments {$post->getPostID()} - {$post->getTitle()}\n");
		$this->sleep();
		$url .= "?";
		$url .= http_build_query($postData);
		$response = HTTP::getFromURL($url, false, false, $this->httpHeaders());
		$json = json_decode($response, true);
		print_r($json);
		$this->sleep();
		
		$p = $json['payload'];
		$total = $p['total'];
		
		$worthy = false;
		
		$ref = null;
		
		foreach ($p['comments'] as $comment)
		{
			$ref = $comment['orderKey'];
			$comment_id = $comment['commentId'];
			$message = $comment['text'];
			
			$user = $p['user'];
			$username = $user['displayName'];
			$user = NG_User::getOrCreate(array(
				'username' => $username,
			));

			if (!($comment = NG_Comment::getBy('ngc_cid', $comment_id)))
			{
				Logger::logCron("New comment by {$user->displayName()} on {$post->displayTitle()}: {$message}");
				NG_PostCommented::commented($user, $post, $comment['timestamp']);
				$comment = NG_Comment::blank(array(
					'ngc_cid' => $comment_id,
					'ngc_user' => $user->getID(),
					'ngc_post' => $post->getID(),
					'ngc_message' => $message,
					'ngc_created' => Time::getDate($comment['timestamp']),
					'ngc_likes' => $comment['likeCount'],
					'ngc_dislikes' => $comment['dislikeCount'],
				))->insert();
			}
			else
			{
				$comment->saveVars(array(
					'ngc_likes' => $comment['likeCount'],
					'ngc_dislikes' => $comment['dislikeCount'],
				), true, $worthy);
				
				if (!$worthy)
				{
					$ref = null;
					break;
				}
			}
		}
		
		$post->saveVars(array(
			'ngp_comment_ref' => $ref,
			'ngp_comments' => $total,
		));
		
		
	}
}
