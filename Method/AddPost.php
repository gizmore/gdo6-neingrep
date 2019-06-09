<?php
namespace GDO\NeinGrep\Method;

use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Submit;
use GDO\Net\GDT_Url;
use GDO\Form\GDT_Validator;
use GDO\Core\GDT;
use GDO\Net\URL;
use GDO\Util\Strings;
use GDO\NeinGrep\NG_Post;
use GDO\Core\GDT_Success;
use GDO\NeinGrep\Scraper;
use GDO\Core\GDT_Error;

/**
 * Add a post manually.
 * Set it's urgency field so it would queued earlier.
 * @author gizmore
 *
 */
final class AddPost extends MethodForm
{
	public function renderPage()
	{
		$menu = $this->templatePHP('page/admin_menu.php');
		return $menu->add(parent::renderPage());
	}
	
	public function createForm(GDT_Form $form)
	{
		$form->addField(GDT_Url::make('url')->reachable());
		$form->addField(GDT_Validator::make()->validator('url', [$this, 'validateURL']));
		$form->addField(GDT_Submit::make());
		$form->addField(GDT_AntiCSRF::make());
	}
	
	/**
	 * 
	 * @param GDT_Form $form
	 * @param GDT $field
	 * @param URL $value
	 */
	public function validateURL(GDT_Form $form, GDT $field, URL $value)
	{
		return preg_match("#^https://9gag.com/gag/[0-9a-zA-Z]{6,16}/?$#D", $value->raw) ? true : $field->error('err_no_9gag_post');
	}
	
	/**
	 * @return URL
	 */
	public function getURL()
	{
		return $this->getForm()->getFormValue('url');
	}
	
	
	public function formValidated(GDT_Form $form)
	{
		if ($url = $this->getURL())
		{
			$this->resetForm();
			$id = Strings::rsubstrFrom(rtrim(trim($url->raw), '/'), '/');
			if ($post = NG_Post::getBy('ngp_nid', $id))
			{
				if ($user = $post->getUser())
				{
					$response = parent::renderPage();
					$success = GDT_Success::responseWith('msg_9gag_op_revealed', [$user->displayName(), $user->hrefProfile()]);
					return $response->add($success);
				}
				else
				{
					$post->saveVar('ngp_urgent', '1');
					$response = parent::renderPage();
					$success = GDT_Success::responseWith('msg_9gag_op_urgent');
					return $response->add($success);
				}
			}
			else
			{
				if (Scraper::make()->scrapePostExists($id))
				{
					NG_Post::blank(array(
						
					))->insert();
					$response = parent::renderPage();
					$success = GDT_Success::responseWith('msg_9gag_op_urgent');
					return $response->add($success);
				}
				else
				{
					$response = parent::renderPage();
					$error = GDT_Error::responseWith('err_no_9gag_post');
					return $response->add($error);
				}
			}
		}
		return $this->renderPage();
	}
	
}
