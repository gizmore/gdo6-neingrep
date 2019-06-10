<?php
namespace GDO\NeinGrep\Method;

use GDO\Table\MethodQueryTable;
use GDO\NeinGrep\NG_User;
use GDO\NeinGrep\NG_UserSectionStats;
use GDO\NeinGrep\NGT_User;
use GDO\NeinGrep\NGT_Section;
use GDO\NeinGrep\NG_Section;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Core\GDT_Response;
use GDO\DB\GDT_UInt;
use GDO\DB\GDT_ObjectSelect;

final class Ranking extends MethodQueryTable
{
	public function gdoParameters()
	{
		return array(
			GDT_ObjectSelect::make('section')->table(NG_Section::table())->choices(NG_Section::table()->all())->initial('1'),
		);
	}
	
	/**
	 * @return NG_Section
	 */
	public function getSection()
	{
		return $this->getForm()->getFormValue('section');
	}
	
	public function getQuery()
	{
		$section = $this->getSection();
		$users = NG_User::table();
		$table = NG_UserSectionStats::table();
		$query = $table->select('*, ng_user.*')->joinObject('nguss_user')->fetchTable($users);
		$query->where("nguss_section = {$section->getID()}");
		return $query;
	}
	
	public function getHeaders()
	{
		return array(
			NGT_User::make('nguss_user'),
			GDT_UInt::make('nguss_posts'),
			GDT_UInt::make('nguss_comments'),
			GDT_UInt::make('ngu_ups'),
			GDT_UInt::make('ngu_downs'),
		);
	}
	
	private $form = null;
	
	public function getForm()
	{
		if ($this->form === null)
		{
			$form = new GDT_Form();
			$form->addField($this->gdoParameter('section'));
			$form->addField(GDT_Submit::make());
			$form->methodGET();
			$this->form = $form;
		}
		return $this->form;
	}
	
	public function execute()
	{
		$form = $this->getForm();
		$response = GDT_Response::makeWith($form);
		return $response->add(parent::execute());
	}

	
}
