<?php

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