<?php
namespace GDO\NeinGrep;

use GDO\DB\GDT_Enum;

final class NGT_Section extends GDT_Enum
{
	
	public function __construct()
	{
		$this->enumValues(...array_keys(NG_Section::allSections()));
	}
	
	public function enumLabel($enumValue=null)
	{
		$all = NG_Section::allSections();
		return html($all[$enumValue]);
	}
	
}
