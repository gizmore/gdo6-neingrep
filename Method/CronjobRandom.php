<?php
namespace GDO\NeinGrep\Method;

use GDO\Cronjob\MethodCronjob;
use GDO\NeinGrep\NG_User;

/**
 * Scrape the next item randomly.
 * @author gizmore
 *
 */
final class CronjobRandom extends MethodCronjob
{
	public function run()
	{
		$user = NG_User::table()->select()->where("ngu_scraped IS NULL")->first()->exec()->fetchObject();
		
		
	}

	
}
