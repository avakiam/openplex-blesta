<?php
// Basics
$lang['Openplex.name'] = 'OpenPlex';
$lang['Openplex.description'] = 'OpenPlex is an open-source hosting control panel for Linux servers: websites, databases, e-mail, DNS and backups, with customer and reseller accounts.';
$lang['Openplex.module_row'] = 'Server';
$lang['Openplex.module_row_plural'] = 'Servers';
$lang['Openplex.module_group'] = 'Server Group';
$lang['Openplex.order_options.first'] = 'First server';
$lang['Openplex.tab_login'] = 'Log in to OpenPlex';

// Servers
$lang['Openplex.add_module_row'] = 'Add Server';
$lang['Openplex.add_module_group'] = 'Add Server Group';
$lang['Openplex.back_to_manage'] = 'Back';
$lang['Openplex.manage.tab_rows'] = 'Servers';
$lang['Openplex.manage.tab_groups'] = 'Server Groups';
$lang['Openplex.manage.rows.name'] = 'Label';
$lang['Openplex.manage.rows.host'] = 'Hostname';
$lang['Openplex.manage.rows.port'] = 'Port';
$lang['Openplex.manage.rows.options'] = 'Options';
$lang['Openplex.manage.rows.none'] = 'There are no servers.';
$lang['Openplex.manage.rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Openplex.manage.groups.name'] = 'Group';
$lang['Openplex.manage.groups.servers'] = 'Servers';
$lang['Openplex.manage.groups.none'] = 'There are no server groups.';
$lang['Openplex.manage.groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Openplex.manage.edit'] = 'Edit';
$lang['Openplex.manage.delete'] = 'Delete';
$lang['Openplex.add_row.box_title'] = 'Add OpenPlex Server';
$lang['Openplex.add_row.submit'] = 'Add Server';
$lang['Openplex.edit_row.box_title'] = 'Edit OpenPlex Server';
$lang['Openplex.edit_row.submit'] = 'Save';
$lang['Openplex.row.server_name'] = 'Label';
$lang['Openplex.row.host_name'] = 'Hostname';
$lang['Openplex.row.host_name.hint'] = "The panel's domain (for example panel.example.com) or its IP address.";
$lang['Openplex.row.use_ssl'] = 'Use HTTPS (recommended: the panel has a domain with a certificate)';
$lang['Openplex.row.port'] = 'Port';
$lang['Openplex.row.port.hint'] = '443 with HTTPS. Without it, 7080: keep that port closed to the internet except to Blesta.';
$lang['Openplex.row.api_key'] = 'API key';
$lang['Openplex.row.api_key.hint'] = "Create it in OpenPlex on API keys, as an administrator (any kind of account) or a reseller (accounts become its customers). It is checked when you save.";

// Packages
$lang['Openplex.package.role'] = 'Account type';
$lang['Openplex.package.role.customer'] = 'Customer';
$lang['Openplex.package.role.reseller'] = 'Reseller';
$lang['Openplex.package.role.tooltip'] = 'A reseller manages customers of its own.';
$lang['Openplex.package.max_domains'] = 'Websites';
$lang['Openplex.package.max_domains.tooltip'] = 'How many websites the account may have; 0 = no limit.';
$lang['Openplex.package.max_databases'] = 'Databases';
$lang['Openplex.package.max_databases.tooltip'] = 'How many databases the account may have; 0 = no limit.';
$lang['Openplex.package.max_db_size_mb'] = 'Database size (MB)';
$lang['Openplex.package.max_db_size_mb.tooltip'] = 'Default size limit of each database; 0 = no limit.';
$lang['Openplex.package.create_website'] = 'Create the website';
$lang['Openplex.package.create_website.tooltip'] = "Create a website for the service's domain (the domain is then required).";
$lang['Openplex.package.runtime'] = 'Website runtime';
$lang['Openplex.package.runtime_version'] = 'Runtime version';
$lang['Openplex.package.runtime_version.tooltip'] = "For example 8.3; empty = the runtime's default.";
$lang['Openplex.package.locale'] = 'Dashboard language';
$lang['Openplex.package.locale.browser'] = "The customer's browser";

// Services
$lang['Openplex.service_field.domain'] = 'Domain';
$lang['Openplex.service_field.domain.tooltip'] = 'The website created for the account, such as example.com.';
$lang['Openplex.service_field.domain.edit_tooltip'] = 'Only recorded here: change the website itself in the panel.';
$lang['Openplex.service_field.username'] = 'Username';
$lang['Openplex.service_field.username.tooltip'] = 'Optional: 3-32 lowercase letters, digits, "_", "." or "-", starting with a letter. Empty = from the domain.';
$lang['Openplex.service_field.password'] = 'Password';
$lang['Openplex.service_field.password.tooltip'] = 'Optional: at least 10 characters with letters and digits. Empty = a generated one (or, when editing, unchanged).';
$lang['Openplex.service_field.id'] = 'OpenPlex account ID';
$lang['Openplex.service_field.id.tooltip'] = 'Only for an existing account added without provisioning it.';
$lang['Openplex.service_field.id.edit_tooltip'] = 'The account this service controls in the panel.';
$lang['Openplex.service_info.account'] = 'Account';
$lang['Openplex.service_info.panel'] = 'Panel';
$lang['Openplex.service_info.login'] = 'Log in to OpenPlex';
$lang['Openplex.service_info.login_admin'] = 'Log in as this customer';
$lang['Openplex.tab_login.intro'] = 'Open the OpenPlex control panel, already signed in, in a new tab. The link works once and lasts two minutes.';
$lang['Openplex.tab_login.button'] = 'Log in to OpenPlex';

// Errors
$lang['Openplex.!error.server_name.empty'] = 'Enter a label for the server.';
$lang['Openplex.!error.host_name.valid'] = 'Enter a valid hostname or IP address.';
$lang['Openplex.!error.port.valid'] = 'Enter a valid port.';
$lang['Openplex.!error.api_key.empty'] = 'Enter an OpenPlex API key.';
$lang['Openplex.!error.api_key.connection'] = "OpenPlex can't be reached with these settings, or the API key is wrong or belongs to a customer account: use an administrator's or a reseller's key.";
$lang['Openplex.!error.meta[max_domains].valid'] = 'The number of websites must be a whole number (0 = no limit).';
$lang['Openplex.!error.meta[max_databases].valid'] = 'The number of databases must be a whole number (0 = no limit).';
$lang['Openplex.!error.meta[max_db_size_mb].valid'] = 'The database size must be a whole number of MB (0 = no limit).';
$lang['Openplex.!error.meta[role].valid'] = 'Choose an account type.';
$lang['Openplex.!error.meta[runtime].valid'] = 'Choose a website runtime.';
$lang['Openplex.!error.meta[runtime_version].valid'] = 'The runtime version is not valid.';
$lang['Openplex.!error.meta[locale].valid'] = 'Choose a dashboard language.';
$lang['Openplex.!error.openplex_domain.format'] = 'Enter a valid domain, such as example.com.';
$lang['Openplex.!error.openplex_username.format'] = 'The username must have 3-32 lowercase letters, digits, "_", "." or "-", starting with a letter.';
$lang['Openplex.!error.openplex_password.format'] = 'The password needs at least 10 characters with letters and digits.';
$lang['Openplex.!error.openplex_id.format'] = 'The account ID must be a number.';
$lang['Openplex.!error.module_row.missing'] = 'No OpenPlex server is available for this package.';
$lang['Openplex.!error.no_account'] = 'The service has no OpenPlex account.';
$lang['Openplex.!error.login.inactive'] = 'The service is not active.';
