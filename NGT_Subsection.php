<?php
namespace GDO\NeinGrep;

use GDO\Form\GDT_Select;

final class NGT_Subsection extends GDT_Select
{
	public static $SECTIONS = array('hot', 'fresh');
	
	public function __construct()
	{
		$this->initial('fresh');
	}
	
	public function initChoices()
	{
		if (!$this->choices)
		{
			$this->choices = array('hot' => t('ng_choice_hot'));
		}
	}
}