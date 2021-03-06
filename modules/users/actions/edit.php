<?php

/**
 * UsersEdit
 *
 * @package		users
 * @subpackage	edit
 *
 * @author 		Tijs Verkoyen <tijs@sumocoders.be>
 * @since		1.0
 */
class UsersEdit extends SiteBaseAction
{
	/**
	 * The form
	 *
	 * @var	SiteForm
	 */
	private $frm;


	/**
	 * The current id
	 *
	 * @var	int
	 */
	private $id;


	/**
	 * The item
	 *
	 * @var	User
	 */
	private $item;


	/**
	 * Execute the action
	 *
	 * @return void
	 */
	public function execute()
	{
		// check if admin
		if(!$this->currentUser->isAdmin)
		{
			Site::displayError('Forbidden', 403);
		}

		$this->id = $this->url->getParameter(1, 'int');
		if($this->id == '')
		{
			Site::displayError('Page not found', 404);
		}

		// check if admin or editing yourself
		if(!$this->currentUser->isAdmin && $this->currentUser->id != $this->id)
		{
			Site::displayError('Forbidden', 403);
		}

		// get item
		$this->item = User::get($this->id);
		if($this->item === false)
		{
			Site::displayError('Page not found', 404);
		}

		$this->loadForm();
		$this->validateForm();
		$this->frm->parse($this->tpl);
		$this->tpl->assign('item', $this->item->toArray());
		$this->display();
	}

	/**
	 * Load the form
	 *
	 * @return void
	 */
	private function loadForm()
	{
		// create form
		$this->frm = new SiteForm('edit');

		// create elements
		$this->frm->addText('email', $this->item->email)->setAttributes(array('type' => 'email', 'required' => null));
		$this->frm->addText('name', $this->item->name)->setAttributes(array('required' => null));
		$this->frm->addPassword('password')->setAttributes(array('autocomplete' => 'off'));
		$this->frm->addDropdown('type', array('user' => 'user', 'admin' => 'admin'), $this->item->type);
	}

	/**
	 * Parse the form
	 */
	private function parse()
	{
		$this->frm->parse($this->tpl);
		$this->tpl->assign('item', $this->item->toArray());
	}

	/**
	 * Validate the form
	 *
	 * @return void
	 */
	private function validateForm()
	{
		// submitted?
		if($this->frm->isSubmitted())
		{
			// validate required fields
			$this->frm->getField('email')->isEmail(SiteLocale::err('EmailIsInvalid'));
			$this->frm->getField('name')->isFilled(SiteLocale::err('FieldIsRequired'));

			// no errors?
			if($this->frm->isCorrect())
			{
				// set properties
				$this->item->name = $this->frm->getField('name')->getValue();
				$this->item->email = $this->frm->getField('email')->getValue();

				if($this->frm->getField('password')->isFilled())
				{
					$this->item->secret = md5(uniqid());
					$this->item->rawPassword = $this->frm->getField('password')->getValue();
				}

				if($this->currentUser->isAdmin)
				{
					$this->item->type = $this->frm->getField('type')->getValue();
				}

				// save
				$this->item->save();

				if($this->currentUser->isAdmin)
				{
					// redirect
					$this->redirect(
						$this->url->buildUrl(
							'index', null, null,
							array('report' => 'edited', 'var' => $this->item->name, 'id' => $this->item->id)
						)
					);
				}

				// redirect
				$this->redirect(
					$this->url->buildUrl(
						'edit', null, $this->item->id,
						array('report' => 'edited', 'var' => $this->item->name, 'id' => $this->item->id)
					)
				);
			}

			// show general error
			else $this->tpl->assign('form' . SpoonFilter::toCamelCase($this->frm->getName()) . 'HasError', true);
		}
	}
}
