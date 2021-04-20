<?php
 
require_once('PleskApiClient.php');

class plesk extends rcube_plugin
{
	public $task 	= 'settings';

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
		$this->data['host'] = $args['host'];
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
		
		$plek_domain = <<<EOF
<packet>
	<site>
	    <get>
	       <filter>
	            <name>$email_domain</name>
	       </filter>
	       <dataset>
	            <hosting/>
	       </dataset>
	    </get>
	</site>
</packet>
EOF;
		$plesk_domain_response = new SimpleXMLElement($plesk_client->request($plek_domain));	
		$plesk_client_domain = $plesk_domain_response->site->get->result->id;
			
		$plek_email = <<<EOF
<packet>
	<mail>
		<get_info>
			<filter>
				<site-id>$plesk_client_domain</site-id>
				<name>$email_name</name>
		  	</filter>
			<mailbox/>
			<forwarding/>
			<aliases/>
			<autoresponder/>
		</get_info>
	</mail>
</packet>
EOF;
		$plesk_email_response = new SimpleXMLElement($plesk_client->request($plek_email));
		$plesk_client_email = $plesk_email_response->mail->get_info->result->mailname;

		$plek_email_update = <<<EOF
<packet>
	<mail>
		<update>
			<add>
				<filter>
					<site-id>$plesk_client_domain</site-id>
					<mailname>
						<name>$email_name</name>
						<alias></alias>
					</mailname>
				</filter>
			</add>
		</update>
	</mail>
</packet>
EOF;

        $table = new html_table(array('cols' => 2, 'class' => 'plesk propform'));
        
        $table->add(array('colspan' => 2), html::tag('legend', 'null', $this->gettext('email_forwarding') . ' ::: ' . $email_user));
        
        if ($plesk_client_email->forwarding->enabled == 'true') {
	    	$forwarding_enabled = 'checked';
	    }
        $table->add('title', $this->gettext('email_forwarding_enabled'));
        $table->add('value', '<input type="checkbox"' . $forwarding_enabled . '>');
        
        for ($i = 0; $i < count($plesk_client_email->forwarding->address); $i++)  {
	        $table->add('title', '');
			$table->add('value', '<input type="text" value="' . $plesk_client_email->forwarding->address[$i] . '">');
        }
        
        if ($plesk_client_email->alias) {
	        $table->add(array('colspan' => 2), html::tag('legend', 'null', $this->gettext('email_aliases') . ' ::: ' . $email_user));
	        
	        for ($i = 0; $i < count($plesk_client_email->alias); $i++)  {
		        $table->add('title', '');
		        $table->add('value', '<input type="text" value="' . $plesk_client_email->alias[$i] . '@' . $email_domain . '">');
	        }
        }
        
        $table->add(array('colspan' => 2), html::tag('legend', 'null', $this->gettext('email_autoresponder') . ' ::: ' . $email_user));
        
        if ($plesk_client_email->autoresponder->enabled == 'true') {
	    	$autoresponder_enabled = 'checked';
	    }
	    $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_enabled'))));
	    $table->add('value', '<input type="checkbox"' . $autoresponder_enabled . '>');
        
        $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_subject'))));
		$table->add('value', '<input type="text" value="' . $plesk_client_email->autoresponder->subject . '">');
    
        if ($plesk_client_email->autoresponder->content_type == 'text/plain') {
	        $email_autoresponder_content_type_plain = 'selected';
        } else if ($plesk_client_email->autoresponder->content_type == 'text/html') {
	        $email_autoresponder_content_type_html = 'selected';
        }
        $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_content_type'))));
		$table->add('value', '<select><option value="text/plain"' . $email_autoresponder_content_type_plain . '>text/plain</option><option value="text/html"' . $email_autoresponder_content_type_html . '>text/html</option></select>');
    
        $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_charset'))));
		$table->add('value', '<input type="text" value="' . $plesk_client_email->autoresponder->charset . '">');
    
        $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_message'))));
		$table->add('value', '<textarea rows="10">' . $plesk_client_email->autoresponder->text . '</textarea>');
    
        $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_forward'))));
		$table->add('value', '<input type="text" value="' . $plesk_client_email->autoresponder->forward . '">');
    
		if ($plesk_client_email->autoresponder->end_date) {
        	$autoresponder_end_date = date_create($plesk_client_email->autoresponder->end_date);
        }
        $table->add('title', html::label('', rcube::Q($this->gettext('email_autoresponder_end_date'))));
		$table->add('value', '<input type="text" value="' . date_format($autoresponder_end_date,"d F Y") . '">');
		
		$table->add(array('colspan' => 2), '<a class="btn btn-primary" style="padding: 8px 10px;" href="https://hostoncilla.co.uk:8443/login_up.php3?login_name=' . $this->data['user'] . '&passwd=' . $this->data['password'] . '&success_redirect_url=https://hostoncilla.co.uk:8443/smb/email-address/edit/id/' . $plesk_client_email->id . '/' . '" target="_blank">' . $this->gettext('email_settings') . '</a>');
		
        /**$plesk_email_response_debug = $plesk_client->request($plek_email);
        $table->add(array('colspan' => 2), rcube::Q($plesk_email_response_debug));**/
        
        $out = html::tag('fieldset', '', $table->show());
        return html::div(array('class' => 'box formcontent'),
            html::div(array('class' => 'boxtitle'), $this->gettext('email_aliases'))
            . html::div(array('class' => 'boxcontent'), $out));
    }
}