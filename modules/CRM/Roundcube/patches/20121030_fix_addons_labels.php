<?php
defined("_VALID_ACCESS") || die('Direct access forbidden');

Utils_RecordBrowserCommon::delete_addon('contact', CRM_Roundcube::module_name(), 'addon');
Utils_RecordBrowserCommon::delete_addon('company', CRM_Roundcube::module_name(), 'addon');
Utils_RecordBrowserCommon::delete_addon('contact', CRM_Roundcube::module_name(), 'mail_addresses_addon');
Utils_RecordBrowserCommon::delete_addon('company', CRM_Roundcube::module_name(), 'mail_addresses_addon');

Utils_RecordBrowserCommon::new_addon('contact', CRM_Roundcube::module_name(), 'addon', _M('E-mails'));
Utils_RecordBrowserCommon::new_addon('company', CRM_Roundcube::module_name(), 'addon', _M('E-mails'));
Utils_RecordBrowserCommon::new_addon('contact', CRM_Roundcube::module_name(), 'mail_addresses_addon', _M('E-mail addresses'));
Utils_RecordBrowserCommon::new_addon('company', CRM_Roundcube::module_name(), 'mail_addresses_addon', _M('E-mail addresses'));

?>
