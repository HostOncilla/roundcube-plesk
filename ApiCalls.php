<?php

$rcmail = rcmail::get_instance();
$user = $rcmail->user;
$storage = $rcmail->get_storage();

if (filter_var($this->data['user'],FILTER_VALIDATE_EMAIL)) {
	$email_user = $this->data['user'];
	$email_domain = array_pop(explode('@', $email_user));
	$email_name = substr($email_user, 0, strrpos($email_user, '@'));
}

$ip = $_SERVER["REMOTE_ADDR"];
$url = 'https://' . $_SERVER["SERVER_NAME"]/** . $_SERVER["REQUEST_URI"]**/;

$plesk_host = $rcmail->config->get('plesk_host');
$plesk_login = $rcmail->config->get('plesk_login');
$plesk_password = $rcmail->config->get('plesk_password');

$plesk_client = new PleskApiClient($plesk_host);
$plesk_client->setCredentials($plesk_login, $plesk_password);

$plek_create_session = <<<EOF
<packet>
	<server>
		<create_session>
			<login>$email_user</login>
			<data>
				<user_ip>$ip</user_ip>
				<source_server>$url</source_server>
			</data>
		</create_session>
	</server>
</packet>
EOF;

$plesk_session_response = new SimpleXMLElement($plesk_client->request($plek_create_session));
$plesk_client_session = $plesk_session_response->server->create_session->result->id;

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
			<mailbox-usage/>
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
