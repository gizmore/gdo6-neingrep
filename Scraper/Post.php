<?php
namespace GDO\NeinGrep\Scraper;

use GDO\NeinGrep\NG_Post;
use GDO\NeinGrep\Scraper;
use GDO\Core\Logger;
use GDO\Date\Time;
use GDO\Net\HTTP;
use GDO\NeinGrep\NG_User;
use GDO\NeinGrep\NG_Comment;
use GDO\NeinGrep\NG_UserSectionStats;

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
		$this->scrapePostComments($post);
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
		$postData['origin'] = "https://9gag.com";
		$username = $post->getVar('ngp_creator') ? $post->getUser()->getName() : 'unknown';
		Logger::logCron("Scraping Post comments {$post->getPostID()} by {$username} - REF {$ref}");
		$this->beforeRequest();
		$url .= "?";
		$url .= http_build_query($postData);
		$response = HTTP::getFromURL($url, false, false, $this->httpHeaders());
		$json = json_decode($response, true);
		
		$nComments = count($json['payload']['comments']);
		Logger::logCron("Got {$nComments} comments.");
		
		$p = $json['payload'];
		$total = $p['total'];
		$opid = (string)$p['opUserId'];
		if (!preg_match("#^u_\\d{4,20}$#D", $opid))
		{
			Logger::logCron("Error: Hidden reveal does not work! OPId: $opid");
		}
		$this->sleep();
		
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
				Logger::logCron("New NG_Comment by {$user->displayName()}: {$message}");
				$comment = NG_Comment::blank(array(
					'ngc_cid' => $comment_id,
					'ngc_user' => $user->getID(),
					'ngc_post' => $post->getID(),
					'ngc_message' => $message,
					'ngc_created' => Time::getDate($commentData['timestamp']),
					'ngc_likes' => $commentData['likeCount'],
					'ngc_dislikes' => $commentData['dislikeCount'],
				))->insert();
				NG_UserSectionStats::updateStatistics($post->getSection(), $user);
			}
			else
			{
				$comment->saveVars(array(
					'ngc_message' => $message,
					'ngc_created' => Time::getDate($commentData['timestamp']),
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
		
		$found = false;
		if ( ($opid) && (!$post->getVar('ngp_creator')) )
		{
			Logger::logCron("Checking hidden OPID {$opid}...");
			if ($op = NG_User::getBy('ngu_uid', $opid))
			{
				$found = true;
				$revealed = !$post->hasCommented($op);
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
		
		if ($found)
		{
			NG_UserSectionStats::updateStatistics($post->getSection(), $op);
		}
	}

}
