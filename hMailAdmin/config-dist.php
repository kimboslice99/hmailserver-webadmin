<?php
// Global configuration.

/*
=== IMPORTANT NOTE ===

http://php.net/manual/en/com.installation.php
"As of PHP 5.3.15 / 5.4.5, this extension requires php_com_dotnet.dll to be enabled inside of php.ini in order to use these functions.
Previous versions of PHP enabled these extensions by default."

[COM_DOT_NET]
extension=php_com_dotnet.dll
*/

/*
   The full URL to the site where PHPWebAdmin will be running. Must end in / and for ssl use https vs http.
   This URL *MUST* work when typed in browser or phpwebadmin will not work sice it's used for redirects.

   Example:

      $hmail_config['rooturl'] = "http://www.mydomain.com/PHPWebAdmin/";
*/

$hmail_config['rooturl'] = "http://localhost/hMailAdmin/";

/*
   The user interface language for PHPWebAdmin. Note that this language
   must be set up as a valid language in hMailServer.ini.

   Example:

      $hmail_config['defaultlanguage'] = "english";
*/

$hmail_config['defaultlanguage'] = "english";

/*

   The rule_editing_level setting defines who should be allowed
   to edit rules. By default, only the server administrator is
   allowed to change the rules.

   You can change this setting if you want domain administrators
   or normal users to be able to edit rules. If you give end-users
   access to rule editing, you should be aware that these users
   may be able to set up rules which will cause problems on the
   server. As an example, they may be able to set up rules which
   generates endless loops of messages.

   Only give end-users or domain administrators rights to rules if
   you trust them to use it wisely.

   If you give domain administrators and end-users access to rules,
   they will only be allowed to edit account-level rules. Server wide
   rules are only accessible by the server administrator.

   Possible values:

      0 - All users on the server should be allowed access.
      1 - Domain administrators and server administrators should be
          allowed access.
      2 - Only server administrators should be allowed access.
*/

$hmail_config['rule_editing_level'] = 2;

/*
   Deny or allow built-in Administrator to login.
   If you deny built-in Administrator you can set admin level to
   "server" on a email account to not lose administrator functionality.

   Possible values:

      0 - Deny Administrator to login.
      1 - Allow Administrator to login.
      2 - Allow Administrator to login from addresses listed in allow_admin_addresses.
*/

$hmail_config['allow_admin_login'] = 1;

/*
   Ip addresses and/or ranges to allow built-in Administrator.
   Range is in IP/CIDR format eg 192.168.0.1/24 fe80::/64
   Separated with ",".

   Example:

      $hmail_config['allow_admin_addresses'] = "192.168.0.0/24,fe80::/64,10.0.0.0/8";
*/

$hmail_config['allow_admin_addresses'] = "";

/*
  Added in web interface redesign to show webmail links to logged in users.
  [domain] is needed if you want the script to dynamically change domains.

  If not set (delete or comment the line), the webmail link will not be shown.

  Example:

    http://webmail.[domain]
    http://[domain]/webmail
*/

$hmail_config['webmail'] = "http://webmail.[domain]";

/*
   First day of week in datepicker js plugin.

   Possible values:

      0 - Sunday
      1 - Monday
      2 - Tuesday
      3 - Wednesday
      4 - Thursday
      5 - Friday
      6 - Saturday
*/

$hmail_config['datepicker_weekStart'] = 1;

/*
	DMARC report analyser.

   Download new reports from IMAP.
   Unpack and / or save in dmarcreport directory.

   REQUIRED: IMAP extension

   The directory "dmarcreports" must exist and have write permission to it.
*/

$hmail_config['dmarc_enable'] = false;

/*
   IMAP account user name
*/

$hmail_config['dmarc_username'] = 'dmarc@example.com';

/*
   IMAP account password
*/

$hmail_config['dmarc_password'] = 'password';

/*
   IMAP server IP
*/

$hmail_config['dmarc_hostip'] = '127.0.0.1';

/*
   IMAP server port
*/

$hmail_config['dmarc_port'] = 993;

/*
   IMAP server encryption

   Possible values:
      ssl   - ssl encryption
      tsl   - tsl encryption
      notls - no encryption
*/

$hmail_config['dmarc_encryption'] = 'ssl';

/*
    tls reporting
*/

$hmail_config['tlsreport_enable'] = false;

$hmail_config['tlsreport_username'] = '';

$hmail_config['tlsreport_password'] = '';

$hmail_config['tlsreport_port'] = 993;

$hmail_config['tlsreport_hostip'] = '127.0.0.1';

$hmail_config['tlsreport_encryption'] = 'ssl';

?>