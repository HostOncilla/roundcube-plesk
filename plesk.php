<?php

/**
 *
 * Plesk
 *
 **/

include_once __dir__ . '/PleskApiClient.php';

class plesk extends rcube_plugin
{
	public $task = 'settings';

	function init()
	{
		$this->register_action('plugin.plesk-autoreply', [$this, 'plesk_autoreply']);
		$this->register_action('plugin.plesk-fowarding', [$this, 'plesk_fowarding']);
		$this->register_action('plugin.plesk-aliases', [$this, 'plesk_aliases']);
		$this->add_hook('settings_actions', [$this, 'settings_actions']);
		$this->add_hook('storage_init', [$this, 'storage_init_hook']);
		$this->load_config();
		$this->add_texts('localization/');
		$this->include_stylesheet($this->local_skin_path() .'/plesk.css');
	}

	function settings_actions($args)
	{

		$args['actions'][] = [
			'action' => 'plugin.plesk-autoreply',
			'class'  => 'plesk-autoreply',
			'label'  => 'plesk_autoreply',
			'domain' => 'plesk',
			'title'  => 'plesk_autoreply',
		];

		$args['actions'][] = [
			'action' => 'plugin.plesk-fowarding',
			'class'  => 'plesk-fowarding',
			'label'  => 'plesk_fowarding',
			'domain' => 'plesk',
			'title'  => 'plesk_fowarding',
		];

		$args['actions'][] = [
			'action' => 'plugin.plesk-aliases',
			'class'  => 'plesk-aliases',
			'label'  => 'plesk_aliases',
			'domain' => 'plesk',
			'title'  => 'plesk_aliases',
		];

		return $args;
	}

	function plesk_autoreply()
	{
		$this->register_handler('plugin.body', [$this, 'autoreply_html']);
		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('plesk_autoreply'));
		$rcmail->output->send('plugin');
	}

	function plesk_fowarding()
	{
		$this->register_handler('plugin.body', [$this, 'fowarding_html']);
		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('plesk_fowarding'));
		$rcmail->output->send('plugin');
	}

	function plesk_aliases()
	{
		$this->register_handler('plugin.body', [$this, 'aliases_html']);
		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('plesk_aliases'));
		$rcmail->output->send('plugin');
	}

	function storage_init_hook($args)
	{
		$this->data['user'] = $args['user'];
		$this->data['password'] = $args['password'];
	}

	function autoreply_html()
	{
		include_once __dir__ . '/ApiCalls.php';

		if ($plesk_client_email->autoresponder->enabled == 'true') {
			$autoreply_checked = 'checked';
		}

		if ($plesk_client_email->autoresponder->end_date) {
			$autoreply_enddate = date_create($plesk_client_email->autoresponder->end_date);
		}

		$autoreply_switch = new html_inputfield(['type' => 'checkbox', $autoreply_checked => $autoreply_checked, 'name' => 'autoreply_switch', 'id' => 'autoreply_switch', 'class' => 'form-check-input']);
		$autoreply_subject = new html_inputfield(['type' => 'text', 'name' => 'autoreply_subject', 'id' => 'autoreply_subject', 'placeholder' => 'Re: <request_subject>', 'value' => 'Re: <request_subject>', 'class' => 'form-control']);
		$autoreply_content_type = new html_select(['name' => 'autoreply_content_type', 'id' => 'autoreply_content_type', 'class' => 'form-control']);
		$autoreply_charset = new html_select(['name' => 'autoreply_content_type', 'id' => 'autoreply_content_type', 'class' => 'form-control']);
		$autoreply_message = new html_textarea(['name' => 'autoreply_message', 'id' => 'autoreply_message', 'class' => 'form-control', 'rows' => 10]);
		$autoreply_forward = new html_inputfield(['type' => 'text', 'name' => 'autoreply_forward', 'id' => 'autoreply_forward', 'class' => 'form-control']);
		$autoreply_end_date = new html_inputfield(['type' => 'text', 'name' => 'autoreply_end_date', 'id' => 'autoreply_end_date', 'class' => 'datepicker form-control']);
		// ====>>> temporarily code, to be deleted //
		$edit_button = new html_button(['type' => 'button', 'onclick' => 'window.open("https://' . $plesk_host . ':8443/enterprise/rsession_init.php?PLESKSESSID=' . $plesk_client_session . '&success_redirect_url=https://' . $plesk_host . ':8443/smb/email-address/edit/id/' . $plesk_client_email->id . '/", "_blank")', 'id' => 'edit_buttom', 'class' => 'btn btn-primary edit']);
		// <<<=== //

		$autoreply = $plesk_client_email->autoresponder->content_type;
		$autoreply_content_type->add($this->gettext('autoreply_text_plain'), 'text/plain');
		$autoreply_content_type->add($this->gettext('autoreply_text_html'), 'text/html');

		$autoreply_charset->add($this->gettext('autoreply_charset_utf8'), 'UTF8');

		$table = new html_table(['cols' => 2, 'class' => 'plesk propform']);

		$table->add('title', html::label('autoreply_switch', $this->gettext('autoreply_switch')));
		$table->add('', $autoreply_switch->show($plesk_client_email->autoresponder->enabled));

		$table->add('title', html::label('auto_subject', $this->gettext('autoreply_subject')));
		$table->add('', $autoreply_subject->show($plesk_client_email->autoresponder->subject));

		$table->add('title', html::label('autoreply_content_type', $this->gettext('autoreply_content_type')));
		$table->add('', $autoreply_content_type->show($autoreply));

		$table->add('title', html::label('autoreply_charset', $this->gettext('autoreply_charset')));
		$table->add('', $autoreply_charset->show($charset));

		$table->add('title', html::label('autoreply_message', $this->gettext('autoreply_message')));
		$table->add('', $autoreply_message->show($plesk_client_email->autoresponder->text));

		$table->add('title', html::label('autoreply_forward', $this->gettext('autoreply_forward')));
		$table->add('', $autoreply_forward->show($plesk_client_email->autoresponder->forward));

		$table->add('title', html::label('autoreply_end_date', $this->gettext('autoreply_end_date')));
		$table->add('', $autoreply_end_date->show(date_format($autoreply_enddate,"d F Y")));

		$table->add('title', html::label('autoreply_attachment', $this->gettext('autoreply_attachment')));
		$table->add('', $plesk_client_email->autoresponder->attachment->{'file-name'});

		$table->add(['colspan' => 2], html::div(['class' => 'formbuttons'], $edit_button->show($this->gettext('email_settings'))));

		$out .= html::tag('fieldset', '', html::tag('legend', null, $this->gettext('plesk_autoreply') . ' ::: ' . $this->data['user']) . $table->show());

		return html::div(['class' => 'box formcontent'], html::div(['class' => 'boxtitle']) . html::div(['class' => 'boxcontent'], $out));
	}

	function fowarding_html()
	{
		include_once __dir__ . '/ApiCalls.php';

		if ($plesk_client_email->forwarding->enabled == 'true') {
			$forwarding_enabled = 'checked';
		}

		$forwarding_switch = new html_inputfield(['type' => 'checkbox', $forwarding_enabled => $forwarding_enabled, 'name' => 'forwarding_switch', 'id' => 'forwarding_switch', 'class' => 'form-check-input']);
		$forwarding_address = new html_inputfield(['type' => 'text', 'name' => 'forwarding_address', 'id' => 'forwarding_address', 'class' => 'form-control']);
		$forwarding_active = new html_inputfield(['type' => 'text', 'name' => 'forwarding_active', 'id' => 'forwarding_active', 'class' => 'form-control']);
		// ====>>> temporarily code, to be deleted //
		$edit_button = new html_button(['type' => 'button', 'onclick' => 'window.open("https://' . $plesk_host . ':8443/enterprise/rsession_init.php?PLESKSESSID=' . $plesk_client_session . '&success_redirect_url=https://' . $plesk_host . ':8443/smb/email-address/edit/id/' . $plesk_client_email->id . '/", "_blank")', 'id' => 'edit_buttom', 'class' => 'btn btn-primary edit']);
		// <<<=== //

		$table = new html_table(['cols' => 2, 'class' => 'plesk propform']);

		$table->add('title', html::label('forwarding_switch', $this->gettext('forwarding_switch')));
		$table->add('', $forwarding_switch->show($plesk_client_email->forwarding->enabled));

		for ($i = 0; $i < count($plesk_client_email->forwarding->address); $i++)  {
			$table->add('title', html::label('fowarding_message', $this->gettext('fowarding_message')));
			$table->add('row input-group', $forwarding_active->show($plesk_client_email->forwarding->address[$i]) . html::span('input-group-append', html::tag('a', ['class' => 'input-group-text icon delete'], html::span('inner', $this->gettext('delete')))));
		}

		$table->add('title', html::label('add_forwarding', $this->gettext('add_forwarding')));
		$table->add('row input-group', $forwarding_address->show() . html::span('input-group-append', html::tag('a', ['class' => 'input-group-text icon delete disabled'], html::span('inner', $this->gettext('delete')))));

		$table->add(['colspan' => 2], html::div(['class' => 'formbuttons'], $edit_button->show($this->gettext('email_settings'))));

		$out .= html::tag('fieldset', '', html::tag('legend', null, $this->gettext('plesk_fowarding')  . ' ::: ' . $this->data['user']) . $table->show());

		return html::div(['class' => 'box formcontent'], html::div(['class' => 'boxtitle']) . html::div(['class' => 'boxcontent'], $out));
	}

	function aliases_html()
	{
		include_once __dir__ . '/ApiCalls.php';

		$alias_active = new html_inputfield(['type' => 'text', 'name' => 'alias_active', 'id' => 'alias_active', 'class' => 'form-control']);
		$alias_address = new html_inputfield(['type' => 'text', 'name' => 'alias_address', 'id' => 'alias_address', 'class' => 'form-control']);
		// ====>>> temporarily code, to be deleted //
		$edit_button = new html_button(['type' => 'button', 'onclick' => 'window.open("https://' . $plesk_host . ':8443/enterprise/rsession_init.php?PLESKSESSID=' . $plesk_client_session . '&success_redirect_url=https://' . $plesk_host . ':8443/smb/email-address/edit/id/' . $plesk_client_email->id . '/", "_blank")', 'id' => 'edit_buttom', 'class' => 'btn btn-primary edit']);
		// <<<=== //

		$table = new html_table(['cols' => 2, 'class' => 'plesk propform']);

		for ($i = 0; $i < count($plesk_client_email->alias); $i++)  {
			$table->add('title', html::label('alias_address', $this->gettext('alias_address') . ' (' . $plesk_client_email->alias[$i] . ')'));
			$table->add('row input-group', $alias_active->show($plesk_client_email->alias[$i]) . html::span('input-group-append', html::span('input-group-text', '@' . $email_domain)) . html::span('input-group-append', html::tag('a', ['class' => 'input-group-text icon delete'], html::span('inner', $this->gettext('delete')))));
		}

		$table->add('title', html::label('add_alias', $this->gettext('add_alias')));
		$table->add('row input-group', $alias_address->show() . html::span('input-group-append', html::span('input-group-text', '@' . $email_domain)) . html::span('input-group-append', html::tag('a', ['class' => 'input-group-text icon delete disabled'], html::span('inner', $this->gettext('delete')))));

		$table->add(['colspan' => 2], html::div(['class' => 'formbuttons'], $edit_button->show($this->gettext('email_settings'))));

		$out .= html::tag('fieldset', '', html::tag('legend', null, $this->gettext('plesk_aliases')  . ' ::: ' . $this->data['user']) . $table->show());

		return html::div(['class' => 'box formcontent'], html::div(['class' => 'boxtitle']) . html::div(['class' => 'boxcontent'], $out));
	}
}
