<?php
namespace GDO\NeinGrep;

use GDO\DB\GDT_ObjectSelect;

final class NGT_Section extends GDT_ObjectSelect
{
	public function __construct()
	{
		$this->table(NG_Section::table());
	}
}
