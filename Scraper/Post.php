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
		Logger::logCron("Scraping post {$post->getPostID()} {$post->getTitle()}");
// 		if ($post->getCommentCount())
		{
			$this->scrapePostComments($post);
		}
		$post->saveVar('ngp_scraped', Time::getDate());
	}
	
	public function scrapePostComments(NG_Post $post)
	{
		$ref = $post->getVar('ngp_comment_ref');
		$url = $this->commentURL();
		$postData = array(
			'appId' => 'a_dd8f2b7d304a10edaf6f29517ea0ca4100a43d1b',
			'url' => $post->hrefGag(),
			'count' => 50,
// 			'order' => 'date',
			'order' => 'score',
		);
		if ($ref)
		{
			$postData['ref'] = $ref;
		}
// 		$postData['auth'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NjAwNTU1ODgsIm5iZiI6MTU2MDA1NTI4OCwiZXhwIjoxNTYwMDk4Nzg4LCJwcml2YXRlIjoicDRRdXBXeCtsUGYwOUJCQW9WWG5QZz09LnFPZ0hGOVFacU5HSkYyWnRYNWpLTUFyaGdEenUxbG1aellzZDVJRTRneTluT1FJenFEVTFER3p4TmFMRWVqeU5qa1F3aFM5eDJGdW9IbkV5TVJFK3lNeFBNeW54NkNJWmthZkRYRWlsWUc1d0Q3TU9XVU9tSWlEaXl2dmtxNElrZkV1dGt0SkQ3WHpmdWx1K0VkYTNmcUZLdzE0SktuNVdsWWVSYWxrVW55YmFaQmk4UmJHeEc2WitPc3hXaXJpeEpsRzY1UUs1bm5LcmtBeXBEQng0djVjRlZiYVBMNWQ4eEdYcXRaY0h4dVdaTmxaeUltcFF3U2tXM0lycHFYenJBV0IzMHJQWGpWejVlQ0k4SnpwRXNoUXZWM0dcL0lKOEtuQ3FZekVMaGRGSFNzZVJEaTdIOEhqQmZtYTJBaGlSUWZiaFwvSE0ya2YwVXF2dFVvWDBObE9XMFJcL2lpY2w0QmxKMHNrcDJPMzNmQ0NjdG5KdjE0RXRYcjhKWjVTZXdJQ3lRT3RXK0ZJMEVXcTBFZEZZd0xmZ01LbllQSmU0QncwNVBSOENSUkd1OUpHb052dmUxaEd0SVkwXC82ejd0ZmNlIn0.cCgVmffgoihzKdBDh6y07RkeFzFtKIulaAMfhUECCeA';
		$postData['origin'] = "https://9gag.com";
		Logger::logCron("Scraping Post comments {$post->getPostID()} by {$post->getVar('ngp_creator')} - REF {$ref}");
		$this->beforeRequest();
		$url .= "?";
		$url .= http_build_query($postData);
// 		echo "$url\n";
		$response = HTTP::getFromURL($url, false, false, $this->httpHeaders());
		$json = json_decode($response, true);
// 		print_r($json);
		
		$nComments = count($json['payload']['comments']);
		Logger::logCron("Got {$nComments} comments.");
		$this->sleep();
		
		$p = $json['payload'];
		$total = $p['total'];
		$opid = $p['opUserId'];
		
		if (!$opid)
		{
			Logger::logCron("Error: Hidden reveal does not work!");
		}
		
		$worthy = false;
		
		$ref = null;
		
		foreach ($p['comments'] as $commentData)
		{
			$ref = $commentData['orderKey'];
			$comment_id = $commentData['commentId'];
			$message = html_entity_decode($commentData['text'], ENT_QUOTES|ENT_HTML5);
			
			$userdata = $commentData['user'];
			$username = $userdata['displayName'];
			$userid = $userdata['userId'];
			$user = NG_User::getOrCreate(array(
				'username' => $username,
			));
			$user->saveVars(array(
				'ngu_uid' => $userid,
				'ngu_last_active' => Time::getDate($userdata['activeTs']),
			));

			if (!($comment = NG_Comment::getBy('ngc_cid', $comment_id)))
			{
				Logger::logCron("New NG_Comment by {$user->displayName()} on {$post->getTitle()}: {$message}");
				NG_PostCommented::commented($user, $post, $commentData['timestamp']);
				$comment = NG_Comment::blank(array(
					'ngc_cid' => $comment_id,
					'ngc_user' => $user->getID(),
					'ngc_post' => $post->getID(),
					'ngc_message' => $message,
					'ngc_created' => Time::getDate($commentData['timestamp']),
					'ngc_likes' => $commentData['likeCount'],
					'ngc_dislikes' => $commentData['dislikeCount'],
				))->insert();
			}
			else
			{
				$comment->saveVars(array(
					'ngc_likes' => $commentData['likeCount'],
					'ngc_dislikes' => $commentData['dislikeCount'],
				), true, $worthy);
				
				if (!$worthy)
				{
					Logger::logCron("Stopping theses comments.");
					$ref = null;
					break;
				}
			}
		}
		
		if (!$post->getVar('ngp_creator'))
		{
			Logger::logCron("Checking hidden OPID {$opid}...");
			if ($op = NG_User::getBy('ngu_uid', $opid))
			{
				$revealed = !NG_PostCommented::hasCommented($op, $post);
				if ($revealed)
				{
					Logger::logCron("Revealed a hidden OP!");
				}
				else
				{
					Logger::logCron("Found OP");
				}
				$post->setVars(array(
					'ngp_uid' => null,
					'ngp_creator' => $op->getID(),
					'ngp_revealed' => $revealed ? Time::getDate() : null,
					'ngp_urgent' => '0',
				));
			}
			else
			{
				$post->setVar('ngp_uid', $opid);
			}
		}
		
		$post->setVars(array(
			'ngp_comment_ref' => $p['hasNext'] ? $ref : null,
			'ngp_comments' => $total,
		));
		
		$post->save();
	}

}
