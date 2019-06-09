<?php
namespace GDO\NeinGrep\Method;

use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\DB\GDT_String;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;
use GDO\NeinGrep\NG_User;
use GDO\User\GDO_User;
use GDO\NeinGrep\Scraper;

final class AddUser extends MethodForm
{
	public function createForm(GDT_Form $form)
	{
		$form->addField(GDT_String::make('ngu_name'));
		$form->addField(GDT_Submit::make());
		$form->addField(GDT_AntiCSRF::make());
	}

	public function formValidated(GDT_Form $form)
	{
		$username = $form->getFormVar('ngu_name');
		if (Scraper::make()->scrapeUserExist($username))
		{
			$user = NG_User::getOrCreate(['username' => $username]);
			$user->saveVar('ngu_creator', GDO_User::current()->getID());
			return parent::formValidated($form);
		}
		return self::renderPage();
	}
	
}
