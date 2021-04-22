<?php
 
require_once('PleskApiClient.php');

class plesk extends rcube_plugin
{
	public $task = 'settings';

	function init()
	{
		$this->add_hook('settings_actions', [$this, 'settings_actions']);
		$this->add_hook('template_object_plugin.body', [$this, 'strinng_replace']);
		$this->add_hook('storage_init', [$this, 'storage_init_hook']);
		$this->register_action('plugin.plesk', [$this, 'infostep']);
		$this->load_config();
		$this->add_texts('localization/');
		$this->include_stylesheet($this->local_skin_path() .'/plesk.css');
	}

	function settings_actions($args)
	{
		$args['actions'][] = [
			'action' => 'plugin.plesk',
			'class'  => 'plesk',
			'label'  => 'plesk',
			'domain' => 'plesk',
			'title'  => 'plesk',
		];

		return $args;
	}

	function infostep()
	{
		$this->register_handler('plugin.body', [$this, 'infohtml']);
		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('plugin'));
		$rcmail->output->send('plugin');
	}

	function storage_init_hook($args)
	{
		$this->data['user'] = $args['user'];
		$this->data['password'] = $args['password'];
	}

	function infohtml()
	{
		$rcmail = rcmail::get_instance();
		$user = $rcmail->user;
		$storage = $rcmail->get_storage();		

		$email_user = $this->data['user'];

		if (filter_var($email_user,FILTER_VALIDATE_EMAIL)) {
			$email_domain = array_pop(explode('@', $email_user));
			$email_name = substr($email_user, 0, strrpos($email_user, '@'));
		}

		$plesk_host = $rcmail->config->get('plesk_host');
		$plesk_login = $rcmail->config->get('plesk_login');
		$plesk_password = $rcmail->config->get('plesk_password');

		$plesk_client = new PleskApiClient($plesk_host);
		$plesk_client->setCredentials($plesk_login, $plesk_password);

		require('ApiCalls.php');

		// Email Fowarding

		if ($plesk_client_email->forwarding->enabled == 'true') {
			$forwarding_enabled = 'checked';
		}

		$forwarding_switch = new html_inputfield(['type' => 'checkbox', $forwarding_enabled => $forwarding_enabled, 'name' => 'forwarding_switch', 'id' => 'forwarding_switch', 'class' => 'form-check-input']);
		$forwarding_address = new html_inputfield(['type' => 'text', 'name' => 'forwarding_address', 'id' => 'forwarding_address', 'class' => 'form-control']);
		$forwarding_active = new html_inputfield(['type' => 'text', 'name' => 'forwarding_active', 'id' => 'forwarding_active', 'class' => 'form-control']);

		$table = new html_table(['cols' => 2, 'class' => 'plesk propform']);

		$table->add('title', html::label('forwarding_switch', $this->gettext('forwarding_switch')));
		$table->add('', $forwarding_switch->show());

		for ($i = 0; $i < count($plesk_client_email->forwarding->address); $i++)  {
			$table->add('', '');
			$table->add('', $forwarding_active->show($plesk_client_email->forwarding->address[$i]));
		}

		$table->add('', '');
		$table->add('', $forwarding_address->show());

		$out .= html::tag('fieldset', '', html::tag('legend', null, $this->gettext('email_forwarding')  . ' ::: ' . $email_user) . $table->show());

		// Email Aliases

		$alias_active = new html_inputfield(['type' => 'text', 'name' => 'alias_active', 'id' => 'alias_active', 'class' => 'form-control']);
		$alias_address = new html_inputfield(['type' => 'text', 'name' => 'alias_address', 'id' => 'alias_address', 'class' => 'form-control']);

		$table = new html_table(['cols' => 2, 'class' => 'plesk propform']);

		for ($i = 0; $i < count($plesk_client_email->alias); $i++)  {
			$table->add('', '');
			$table->add('row input-group', $alias_active->show($plesk_client_email->alias[$i]) . html::span('input-group-append', '') . html::span('input-group-text', '@' . $email_domain));
		}

		$table->add('', '');
		$table->add('row input-group', $alias_address->show() . html::span('input-group-append', '') . html::span('input-group-text', '@' . $email_domain));

		$out .= html::tag('fieldset', '', html::tag('legend', null, $this->gettext('email_aliases')  . ' ::: ' . $email_user) . $table->show());

		// Auto Reply

		if ($plesk_client_email->autoresponder->enabled == 'true') {
			$autoreply_checked = 'checked';
		}

		if ($plesk_client_email->autoresponder->end_date) {
			$autoreply_enddate = date_create($plesk_client_email->autoresponder->end_date);
		}

		$autoreply_switch = new html_inputfield(['type' => 'checkbox', $autoreply_checked => $autoreply_checked, 'name' => 'autoreply_switch', 'id' => 'autoreply_switch', 'class' => 'form-check-input']);
		$autoreply_subject = new html_inputfield(['type' => 'text', 'name' => 'autoreply_subject', 'id' => 'autoreply_subject', 'class' => 'form-control']);
		$autoreply_content_type = new html_select(['name' => 'autoreply_content_type', 'id' => 'autoreply_content_type', 'class' => 'form-control']);
		$autoreply_charset = new html_select(['name' => 'autoreply_content_type', 'id' => 'autoreply_content_type', 'class' => 'form-control']);
		$autoreply_message = new html_textarea(['name' => 'autoreply_message', 'id' => 'autoreply_message', 'class' => 'form-control', 'rows' => 10]);
		$autoreply_forward = new html_inputfield(['type' => 'text', 'name' => 'autoreply_forward', 'id' => 'autoreply_forward', 'class' => 'form-control']);
		$autoreply_end_date = new html_inputfield(['type' => 'text', 'name' => 'autoreply_end_date', 'id' => 'autoreply_end_date', 'class' => 'datepicker form-control']);

		$autoreply = $plesk_client_email->autoresponder->content_type;
		$autoreply_content_type->add($this->gettext('autoreply_text_plain'), 'text/plain');
		$autoreply_content_type->add($this->gettext('autoreply_text_html'), 'text/html');

		$autoreply_charset->add($this->gettext('autoreply_charset_utf8'), 'UTF8');

		$table = new html_table(['cols' => 2, 'class' => 'plesk propform']);

		$table->add('title', html::label('autoreply_switch', $this->gettext('autoreply_switch')));
		$table->add('', $autoreply_switch->show($plesk_client_email->autoresponder->content_type));

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

		$table->add(['colspan' => 2], '<div class="formbuttons"><a href="https://' . $plesk_host . ':8443/login_up.php3?login_name=' . $this->data['user'] . '&passwd=' . $this->data['password'] . '&success_redirect_url=https://' . $plesk_host . ':8443/smb/email-address/edit/id/' . $plesk_client_email->id . '/' . '" target="_blank"><button class="btn btn-primary edit">' . $this->gettext('email_settings') . '</button></a></div>');

		$out .= html::tag('fieldset', '', html::tag('legend', null, $this->gettext('email_autoreply') . ' ::: ' . $email_user) . $table->show());

		return html::div(['class' => 'box formcontent'], html::div(['class' => 'boxtitle']) . html::div(['class' => 'boxcontent'], $out));

	}
}
