<?php
namespace GDO\NeinGrep;

use GDO\DB\GDT_Object;
use GDO\UI\GDT_Link;

final class NGT_User extends GDT_Object
{
	public function defaultLabel() { return $this->label('ngu_name'); }
	
	public function __construct()
	{
		$this->table(NG_User::table());
	}
	
	/**
	 * @return \GDO\NeinGrep\NG_Post
	 */
	public function getPost()
	{
		return $this->gdo;
	}
	
	public function getUser()
	{
		if ($this->gdo instanceof NG_User)
		{
			return $this->gdo;
		}
		return $this->getPost()->getUser();
	}
	
	public function renderCell()
	{
		if ($user = $this->getUser())
		{
			return GDT_Link::make()->targetBlank()->href(Scraper::make()->neinURL()."u/".$user->getName())->rawLabel($user->displayName())->renderCell();
		}
		return t('unknown');
	}
}
