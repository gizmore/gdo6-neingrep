<?php
namespace GDO\NeinGrep;

use GDO\DB\GDT_Object;
use GDO\UI\GDT_Link;

final class NGT_Post extends GDT_Object
{
	public function defaultLabel() { return $this->label('ngp_nid'); }
	
	public function __construct()
	{
		$this->table(NG_Post::table());
	}
	
	/**
	 * @return \GDO\NeinGrep\NG_Post
	 */
	public function getPost()
	{
		return $this->gdo;
	}
	
	public function renderCell()
	{
		$post = $this->getPost();
		return GDT_Link::make()->href(Scraper::make()->neinURL()."gag/".$post->getPostID())->rawLabel($post->getPostID())->renderCell();
	}
}
