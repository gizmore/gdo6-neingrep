<?php
namespace GDO\NeinGrep;

use GDO\Net\HTTP;

/**
 * Basic scraper class. util.
 * @author gizmore
 */
class Scraper
{
	/**
	 * @return self
	 */
	public static function make()
	{
		$class = get_called_class();
		return new $class();
	}
	
	
	public $channel = 'hot';
	public function channel($channel='hot')
	{
		$this->channel = $channel;
		return $this;
	}
	
	
	public function httpHeaders()
	{
		return array(
			'accept' => 'application/json',
		);
	}
	
	public function sleep()
	{
		echo "sleeping...\n";
		usleep(Module_NeinGrep::instance()->cfgRequestSleepMicros());
	}
	
	public function beforeRequest()
	{
		$this->sleep();
		Module_NeinGrep::instance()->increaseRequestCounter();
	}
	
	public function neinURL()
	{
		return "https://9gag.com/";
	}
	
	public function apiURL()
	{
		return $this->neinURL() . "v1/";
	}
	
	public function postURL(NG_Post $post)
	{
		return "https://9gag.com/gag/{$post->getNID()}";
	}
	
	public function postImageURL(NG_Post $post)
	{
		return "https://img-9gag-fun.9cache.com/photo/{$post->getNID()}";
	}
	
	public function scrapeUserExist(string $user_name)
	{
		$url = $this->neinURL() . "u/" . $user_name;
		return HTTP::pageExists($url);
	}
	
	public function scrapePostExists(string $post_id)
	{
		$url = $this->neinURL() . "gag/" . $post_id;
		return HTTP::pageExists($url);
	}
		
}
