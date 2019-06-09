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
use GDO\Core\GDT_Success;

final class AddUser extends MethodForm
{
	public function renderPage()
	{
		$menu = $this->templatePHP('page/admin_menu.php');
		return $menu->add(parent::renderPage());
	}
	
	public function createForm(GDT_Form $form)
	{
		$form->addField(GDT_String::make('ngu_name'));
		$form->addField(GDT_Submit::make());
		$form->addField(GDT_AntiCSRF::make());
	}

	public function formValidated(GDT_Form $form)
	{
		if ($username = $form->getFormVar('ngu_name'))
		{
			if (Scraper::make()->scrapeUserExist($username))
			{
				$user = NG_User::getOrCreate(['username' => $username]);
				$user->saveVar('ngu_creator', GDO_User::current()->getID());
				$response = $this->renderPage();
				return $response->add(GDT_Success::responseWith('msg_9gag_user_added'));
			}
		}
		return $this->renderPage();
	}
	
}
