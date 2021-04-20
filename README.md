Plesk Plugin for Roundcube
========================================

Roundcube plugin that adds `Email Fowarding`, `Email Aliases`, and `Auto Reply` to Roundcube. `Can be used to log in to Plesk` setting has to be setup within Plesk for each individual email accounts.

This plugin is very much in very early stages. I'm not by any means an expert, much help is needed in finishing this plugin.
At the monet you can only view the options within Roundcube.

## License

This plugin is released under the <a href="https://www.gnu.org/licenses/gpl.html">GNU General Public License Version 3+</a>.

## Installation

* Rename the plugin folder to `plesk`
* Upload `plesk` folder to your Roundcube `plugins` directory
* Add `plesk` to `$config['plugins']` in your Roundcube `config/config.inc.php`

## Configuration

You can customize some settings :

* Rename `plugins/plesk/config.inc.php.dist` to `plugins/plesk/config.inc.php`
* Edit `plugins/plesk/config.inc.php` as you fancy
