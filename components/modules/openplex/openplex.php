<?php
/**
 * OpenPlex provisioning module for Blesta.
 *
 * Creates, suspends, unsuspends and cancels OpenPlex accounts, changes their
 * limits and password, and signs customers in to the panel from the client
 * area. It talks to the OpenPlex REST API with an API key.
 *
 * @see https://github.com/avakiam/openplex-blesta
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Client.php';

use OpenPlex\Blesta\ApiError;
use OpenPlex\Blesta\Client;

#[\AllowDynamicProperties]
class Openplex extends Module
{
    /** Package settings, with their defaults. */
    const PACKAGE_DEFAULTS = [
        'role' => 'customer',
        'max_domains' => '1',
        'max_databases' => '1',
        'max_db_size_mb' => '0',
        'create_website' => 'true',
        'runtime' => 'php',
        'runtime_version' => '',
        'locale' => '',
    ];

    const RUNTIMES = ['php' => 'PHP', 'static' => 'Static', 'node' => 'Node.js', 'python' => 'Python', 'go' => 'Go'];

    public function __construct()
    {
        $this->loadConfig(__DIR__ . DS . 'config.json');
        Loader::loadComponents($this, ['Input']);
        Language::loadLang('openplex', null, __DIR__ . DS . 'language' . DS);
        Configure::load('openplex', __DIR__ . DS . 'config' . DS);
    }

    // --- servers (module rows) ---------------------------------------------

    public function manageModule($module, array &$vars)
    {
        return $this->render('manage', ['module' => $module]);
    }

    public function manageAddRow(array &$vars)
    {
        if (!empty($vars) && empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }
        return $this->render('add_row', ['module' => $this->moduleRecord(), 'vars' => (object) $vars]);
    }

    public function manageEditRow($module_row, array &$vars)
    {
        if (empty($vars)) {
            $vars = (array) $module_row->meta;
        } elseif (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }
        return $this->render('edit_row', ['module' => $this->moduleRecord(), 'vars' => (object) $vars]);
    }

    public function addModuleRow(array &$vars)
    {
        return $this->rowMeta($vars);
    }

    public function editModuleRow($module_row, array &$vars)
    {
        return $this->rowMeta($vars);
    }

    public function deleteModuleRow($module_row)
    {
    }

    public function getGroupOrderOptions()
    {
        return ['first' => Language::_('Openplex.order_options.first', true)];
    }

    public function selectModuleRow($module_group_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }
        $group = $this->ModuleManager->getGroup($module_group_id);
        if ($group) {
            foreach ($group->rows as $row) {
                return $row->id;
            }
        }
        return 0;
    }

    /**
     * Validates a server's settings (and that its API key works and isn't a
     * customer's) and returns them as meta fields; the API key is encrypted.
     */
    private function rowMeta(array &$vars)
    {
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }
        $vars['host_name'] = strtolower(trim((string) ($vars['host_name'] ?? '')));
        $vars['api_key'] = trim((string) ($vars['api_key'] ?? ''));
        if (trim((string) ($vars['port'] ?? '')) === '') {
            $vars['port'] = $vars['use_ssl'] === 'true' ? '443' : '7080';
        }
        $this->Input->setRules([
            'server_name' => ['empty' => [
                'rule' => 'isEmpty', 'negate' => true,
                'message' => Language::_('Openplex.!error.server_name.empty', true),
            ]],
            'host_name' => ['valid' => [
                'rule' => [[$this, 'validateHost']],
                'message' => Language::_('Openplex.!error.host_name.valid', true),
            ]],
            'port' => ['valid' => [
                'rule' => ['matches', '/^[0-9]{1,5}$/'],
                'message' => Language::_('Openplex.!error.port.valid', true),
            ]],
            'api_key' => [
                'empty' => [
                    'rule' => 'isEmpty', 'negate' => true,
                    'message' => Language::_('Openplex.!error.api_key.empty', true),
                ],
                'connection' => [
                    'rule' => [[$this, 'validateConnection'], $vars['host_name'], $vars['port'], $vars['use_ssl']],
                    'message' => Language::_('Openplex.!error.api_key.connection', true),
                ],
            ],
        ]);
        if (!$this->Input->validates($vars)) {
            return null;
        }
        $meta = [];
        foreach (['server_name', 'host_name', 'port', 'use_ssl', 'api_key'] as $key) {
            $meta[] = ['key' => $key, 'value' => $vars[$key], 'encrypted' => $key === 'api_key' ? 1 : 0];
        }
        return $meta;
    }

    /** A domain name or an IP address. */
    public function validateHost($host)
    {
        $host = trim((string) $host, '[]');
        return filter_var($host, FILTER_VALIDATE_IP) !== false
            || (bool) preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i', $host);
    }

    /** The API key works and belongs to an administrator or a reseller. */
    public function validateConnection($key, $host, $port, $use_ssl)
    {
        try {
            $me = $this->client((object) ['host_name' => $host, 'port' => $port, 'use_ssl' => $use_ssl, 'api_key' => $key])->get('/auth/me');
            return in_array($me['role'] ?? '', ['admin', 'reseller'], true);
        } catch (Exception $e) {
            return false;
        }
    }

    // --- packages -----------------------------------------------------------

    public function getPackageFields($vars = null)
    {
        $meta = (array) ($vars->meta ?? []);
        $value = function ($key) use ($meta) {
            return isset($meta[$key]) ? $meta[$key] : self::PACKAGE_DEFAULTS[$key];
        };
        $fields = new ModuleFields();

        $this->select($fields, 'role', $value('role'), [
            'customer' => Language::_('Openplex.package.role.customer', true),
            'reseller' => Language::_('Openplex.package.role.reseller', true),
        ], 'Openplex.package.role.tooltip');
        foreach (['max_domains', 'max_databases', 'max_db_size_mb'] as $key) {
            $label = $fields->label(Language::_('Openplex.package.' . $key, true), 'openplex_' . $key);
            $label->attach($fields->fieldText('meta[' . $key . ']', $value($key), ['id' => 'openplex_' . $key]));
            $label->attach($fields->tooltip(Language::_('Openplex.package.' . $key . '.tooltip', true)));
            $fields->setField($label);
        }
        $label = $fields->label(Language::_('Openplex.package.create_website', true), 'openplex_create_website');
        $label->attach($fields->fieldCheckbox('meta[create_website]', 'true', $value('create_website') === 'true', ['id' => 'openplex_create_website']));
        $label->attach($fields->tooltip(Language::_('Openplex.package.create_website.tooltip', true)));
        $fields->setField($label);
        $this->select($fields, 'runtime', $value('runtime'), self::RUNTIMES, null);
        $label = $fields->label(Language::_('Openplex.package.runtime_version', true), 'openplex_runtime_version');
        $label->attach($fields->fieldText('meta[runtime_version]', $value('runtime_version'), ['id' => 'openplex_runtime_version']));
        $label->attach($fields->tooltip(Language::_('Openplex.package.runtime_version.tooltip', true)));
        $fields->setField($label);
        $this->select($fields, 'locale', $value('locale'), [
            '' => Language::_('Openplex.package.locale.browser', true),
            'en' => 'English', 'es' => 'Español', 'ca' => 'Català',
        ], null);
        return $fields;
    }

    private function select(ModuleFields $fields, $key, $selected, array $options, $tooltip)
    {
        $label = $fields->label(Language::_('Openplex.package.' . $key, true), 'openplex_' . $key);
        $label->attach($fields->fieldSelect('meta[' . $key . ']', $options, $selected, ['id' => 'openplex_' . $key]));
        if ($tooltip !== null) {
            $label->attach($fields->tooltip(Language::_($tooltip, true)));
        }
        $fields->setField($label);
    }

    public function addPackage(array $vars = null)
    {
        return $this->packageMeta($vars);
    }

    public function editPackage($package, array $vars = null)
    {
        return $this->packageMeta($vars);
    }

    private function packageMeta(array $vars = null)
    {
        $meta = (array) ($vars['meta'] ?? []);
        $meta['create_website'] = ($meta['create_website'] ?? '') === 'true' ? 'true' : 'false';
        $vars['meta'] = $meta;
        $number = function ($key) {
            return ['meta[' . $key . ']' => ['valid' => [
                'rule' => ['matches', '/^[0-9]{1,9}$/'],
                'message' => Language::_('Openplex.!error.meta[' . $key . '].valid', true),
            ]]];
        };
        $this->Input->setRules($number('max_domains') + $number('max_databases') + $number('max_db_size_mb') + [
            'meta[role]' => ['valid' => [
                'rule' => ['in_array', ['customer', 'reseller']],
                'message' => Language::_('Openplex.!error.meta[role].valid', true),
            ]],
            'meta[runtime]' => ['valid' => [
                'rule' => ['array_key_exists', self::RUNTIMES],
                'message' => Language::_('Openplex.!error.meta[runtime].valid', true),
            ]],
            'meta[runtime_version]' => ['valid' => [
                'rule' => ['matches', '/^[0-9A-Za-z.\-]{0,16}$/'],
                'message' => Language::_('Openplex.!error.meta[runtime_version].valid', true),
            ]],
            'meta[locale]' => ['valid' => [
                'rule' => ['in_array', ['', 'en', 'es', 'ca']],
                'message' => Language::_('Openplex.!error.meta[locale].valid', true),
            ]],
        ]);
        if (!$this->Input->validates($vars)) {
            return null;
        }
        $out = [];
        foreach (self::PACKAGE_DEFAULTS as $key => $default) {
            $out[] = ['key' => $key, 'value' => trim((string) ($meta[$key] ?? $default)), 'encrypted' => 0];
        }
        return $out;
    }

    // --- service fields -----------------------------------------------------

    public function getClientAddFields($package, $vars = null)
    {
        $fields = new ModuleFields();
        $this->domainField($fields, $vars);
        return $fields;
    }

    public function getAdminAddFields($package, $vars = null)
    {
        $fields = new ModuleFields();
        $this->domainField($fields, $vars);
        $this->textField($fields, 'openplex_username', $vars, 'Openplex.service_field.username.tooltip');
        $this->passwordField($fields, $vars);
        $this->textField($fields, 'openplex_id', $vars, 'Openplex.service_field.id.tooltip');
        return $fields;
    }

    public function getAdminEditFields($package, $vars = null)
    {
        $fields = new ModuleFields();
        $this->textField($fields, 'openplex_domain', $vars, 'Openplex.service_field.domain.edit_tooltip');
        $this->textField($fields, 'openplex_username', $vars, null);
        $this->passwordField($fields, (object) ['openplex_password' => '']);
        $this->textField($fields, 'openplex_id', $vars, 'Openplex.service_field.id.edit_tooltip');
        return $fields;
    }

    private function domainField(ModuleFields $fields, $vars)
    {
        $value = $vars->openplex_domain ?? ($vars->domain ?? null);
        $label = $fields->label(Language::_('Openplex.service_field.domain', true), 'openplex_domain');
        $label->attach($fields->fieldText('openplex_domain', $value, ['id' => 'openplex_domain']));
        $label->attach($fields->tooltip(Language::_('Openplex.service_field.domain.tooltip', true)));
        $fields->setField($label);
    }

    private function textField(ModuleFields $fields, $name, $vars, $tooltip)
    {
        $key = substr($name, strlen('openplex_'));
        $label = $fields->label(Language::_('Openplex.service_field.' . $key, true), $name);
        $label->attach($fields->fieldText($name, $vars->$name ?? null, ['id' => $name]));
        if ($tooltip !== null) {
            $label->attach($fields->tooltip(Language::_($tooltip, true)));
        }
        $fields->setField($label);
    }

    private function passwordField(ModuleFields $fields, $vars)
    {
        $label = $fields->label(Language::_('Openplex.service_field.password', true), 'openplex_password');
        $label->attach($fields->fieldPassword('openplex_password', ['id' => 'openplex_password', 'value' => $vars->openplex_password ?? null]));
        $label->attach($fields->tooltip(Language::_('Openplex.service_field.password.tooltip', true)));
        $fields->setField($label);
    }

    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->serviceRules($package, $vars));
        return $this->Input->validates($vars);
    }

    public function validateServiceEdit($service, array $vars = null)
    {
        $this->Input->setRules($this->serviceRules(null, $vars));
        return $this->Input->validates($vars);
    }

    private function serviceRules($package, array $vars = null)
    {
        $needsDomain = $package !== null && ($package->meta->create_website ?? 'false') === 'true'
            && empty($vars['openplex_id']);
        return [
            'openplex_domain' => ['format' => [
                'if_set' => !$needsDomain,
                'rule' => [[$this, 'validateDomain']],
                'message' => Language::_('Openplex.!error.openplex_domain.format', true),
            ]],
            'openplex_username' => ['format' => [
                'if_set' => true,
                'rule' => ['matches', '/^([a-z][a-z0-9_.\-]{2,31})?$/'],
                'message' => Language::_('Openplex.!error.openplex_username.format', true),
            ]],
            'openplex_password' => ['format' => [
                'if_set' => true,
                'rule' => [[$this, 'validatePassword']],
                'message' => Language::_('Openplex.!error.openplex_password.format', true),
            ]],
            'openplex_id' => ['format' => [
                'if_set' => true,
                'rule' => ['matches', '/^([1-9][0-9]*)?$/'],
                'message' => Language::_('Openplex.!error.openplex_id.format', true),
            ]],
        ];
    }

    public function validateDomain($domain)
    {
        $domain = strtolower(trim((string) $domain));
        return $domain === '' ? false : (bool) preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,62}$/', $domain);
    }

    /** Empty (keep or generate one), or what OpenPlex accepts. */
    public function validatePassword($password)
    {
        return $password === '' || self::passwordOk($password);
    }

    // --- service lifecycle --------------------------------------------------

    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = 'pending')
    {
        $row = $this->getModuleRow();
        if (!$row) {
            $this->Input->setErrors(['module_row' => ['missing' => Language::_('Openplex.!error.module_row.missing', true)]]);
            return null;
        }
        $vars['openplex_domain'] = strtolower(trim((string) ($vars['openplex_domain'] ?? '')));
        if (!$this->validateService($package, $vars)) {
            return null;
        }
        $username = $this->username((string) ($vars['openplex_username'] ?? ''), $vars['openplex_domain']);
        $password = (string) ($vars['openplex_password'] ?? '');
        if (!self::passwordOk($password)) {
            $password = self::generatePassword();
        }
        $id = (string) ($vars['openplex_id'] ?? '');

        if (($vars['use_module'] ?? 'false') === 'true') {
            try {
                $api = $this->client($row->meta);
                $email = '';
                if (!empty($vars['client_id'])) {
                    Loader::loadModels($this, ['Clients']);
                    $client = $this->Clients->get($vars['client_id'], false);
                    $email = $client ? (string) $client->email : '';
                }
                $body = $this->limits($package) + [
                    'password' => $password,
                    'email' => $email,
                    'role' => $this->meta($package, 'role'),
                    'locale' => $this->meta($package, 'locale'),
                ];
                // The name may be taken: try a few variations.
                $user = null;
                for ($try = 0; $user === null; $try++) {
                    $candidate = $try === 0 ? $username : substr($username, 0, 28) . random_int(100, 999);
                    try {
                        $user = $api->post('/users', ['username' => $candidate] + $body);
                    } catch (ApiError $e) {
                        if ($e->status !== 409 || $try >= 4) {
                            throw $e;
                        }
                    }
                }
                $username = $user['username'];
                $id = (string) $user['id'];

                if ($vars['openplex_domain'] !== '' && $this->meta($package, 'create_website') === 'true') {
                    $site = ['name' => $vars['openplex_domain'], 'owner_id' => (int) $id, 'runtime' => $this->meta($package, 'runtime')];
                    if ($this->meta($package, 'runtime_version') !== '') {
                        $site['runtime_version'] = $this->meta($package, 'runtime_version');
                    }
                    try {
                        $api->post('/domains', $site);
                    } catch (ApiError $e) {
                        // The account stays (Blesta records it): the website can be added from the panel.
                        $this->log($row->meta->host_name . '|website', 'The account was created, but not its website: ' . $e->getMessage(), 'output', false);
                    }
                }
            } catch (Exception $e) {
                $this->Input->setErrors(['api' => ['error' => $e->getMessage()]]);
                return null;
            }
        }

        return $this->serviceMeta($vars['openplex_domain'], $username, $password, $id);
    }

    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $fields = $this->serviceFieldsToObject($service->fields);
        $vars = (array) $vars;
        if (!$this->validateServiceEdit($service, $vars)) {
            return null;
        }
        $domain = isset($vars['openplex_domain']) ? strtolower(trim($vars['openplex_domain'])) : ($fields->openplex_domain ?? '');
        $username = isset($vars['openplex_username']) && $vars['openplex_username'] !== '' ? $vars['openplex_username'] : ($fields->openplex_username ?? '');
        $password = $fields->openplex_password ?? '';
        $id = isset($vars['openplex_id']) ? (string) $vars['openplex_id'] : ($fields->openplex_id ?? '');
        $newPassword = (string) ($vars['openplex_password'] ?? '');

        if ($newPassword !== '' && $newPassword !== $password) {
            if (($vars['use_module'] ?? 'false') === 'true') {
                try {
                    $row = $this->getModuleRow();
                    $api = $this->client($row->meta);
                    $accountId = $this->accountId($api, $service, $id, $username);
                    if ($accountId === null) {
                        throw new RuntimeException(Language::_('Openplex.!error.no_account', true));
                    }
                    $api->post("/users/$accountId/password", ['password' => $newPassword]);
                } catch (Exception $e) {
                    $this->Input->setErrors(['api' => ['error' => $e->getMessage()]]);
                    return null;
                }
            }
            $password = $newPassword;
        }
        return $this->serviceMeta($domain, $username, $password, $id);
    }

    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->userAction($service, function (Client $api, $id) {
            $api->post("/users/$id/suspend");
        });
    }

    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->userAction($service, function (Client $api, $id) {
            $api->post("/users/$id/unsuspend");
        });
    }

    /** Deletes the account with its websites (files, mail, DNS) and databases. */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        try {
            $row = $this->getModuleRow();
            if (!$row) {
                return null;
            }
            $api = $this->client($row->meta);
            $fields = $this->serviceFieldsToObject($service->fields);
            $id = $this->accountId($api, $service, $fields->openplex_id ?? '', $fields->openplex_username ?? '');
            if ($id === null) {
                return null; // nothing left in the panel
            }
            try {
                $api->delete("/users/$id?purge=true");
            } catch (ApiError $e) {
                if ($e->status !== 404) { // 404: already deleted in the panel
                    throw $e;
                }
            }
            return $this->serviceMeta($fields->openplex_domain ?? '', $fields->openplex_username ?? '', $fields->openplex_password ?? '', '');
        } catch (Exception $e) {
            $this->Input->setErrors(['api' => ['error' => $e->getMessage()]]);
            return null;
        }
    }

    public function changeServicePackage($package_from, $package_to, $service, $parent_package = null, $parent_service = null)
    {
        return $this->userAction($service, function (Client $api, $id) use ($package_to) {
            $api->patch("/users/$id", $this->limits($package_to) + ['role' => $this->meta($package_to, 'role')]);
        });
    }

    // --- service pages and single sign-on -----------------------------------

    public function getAdminServiceInfo($service, $package)
    {
        return $this->render('admin_service_info', $this->infoVars($service, $package)
            + ['login_url' => $this->base_uri . 'clients/servicetab/' . $service->client_id . '/' . $service->id . '/tabLogin/']);
    }

    public function getClientServiceInfo($service, $package)
    {
        return $this->render('client_service_info', $this->infoVars($service, $package)
            + ['login_url' => $this->base_uri . 'services/manage/' . $service->id . '/tabClientLogin/']);
    }

    public function getAdminTabs($package)
    {
        return ['tabLogin' => Language::_('Openplex.tab_login', true)];
    }

    public function getClientTabs($package)
    {
        return ['tabClientLogin' => Language::_('Openplex.tab_login', true)];
    }

    /** Admin tab: signs the admin in to the panel as the service's account. */
    public function tabLogin($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->loginTab($service, $post, $this->base_uri . 'clients/servicetab/' . $service->client_id . '/' . $service->id . '/tabLogin/');
    }

    /** Client tab: signs the customer in to the panel. */
    public function tabClientLogin($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->loginTab($service, $post, $this->base_uri . 'services/manage/' . $service->id . '/tabClientLogin/');
    }

    /**
     * Shows a "Log in to OpenPlex" button; posting it creates a one-time,
     * two-minute sign-in link and redirects to it.
     */
    private function loginTab($service, $post, $action)
    {
        $error = null;
        if (!empty($post['openplex_login'])) {
            if ($service->status !== 'active') {
                $error = Language::_('Openplex.!error.login.inactive', true);
            } else {
                $url = $this->signInLink($service, $error);
                if ($url !== null) {
                    $this->redirect($url);
                    return '';
                }
            }
        }
        return $this->render('tab_login', ['action' => $action, 'error' => $error]);
    }

    /** A one-time sign-in link for the service's account, or null with $error set. */
    public function signInLink($service, &$error = null)
    {
        try {
            $row = $this->getModuleRow($service->module_row_id ?? null);
            if (!$row) {
                throw new RuntimeException(Language::_('Openplex.!error.module_row.missing', true));
            }
            $api = $this->client($row->meta);
            $fields = $this->serviceFieldsToObject($service->fields);
            $id = $this->accountId($api, $service, $fields->openplex_id ?? '', $fields->openplex_username ?? '');
            if ($id === null) {
                throw new RuntimeException(Language::_('Openplex.!error.no_account', true));
            }
            $link = $api->post("/users/$id/login-link", ['expires_in_seconds' => 120]);
            return !empty($link['url']) ? $link['url'] : $api->panelUrl() . $link['path'];
        } catch (Exception $e) {
            $error = $e->getMessage();
            return null;
        }
    }

    /** Sends the browser to $url (separate so that tests can catch it). */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    private function infoVars($service, $package)
    {
        $row = $this->getModuleRow($service->module_row_id ?? null);
        $panel = '';
        if ($row) {
            $panel = $this->client($row->meta)->panelUrl();
        }
        return ['service_fields' => $this->serviceFieldsToObject($service->fields), 'panel_url' => $panel, 'service' => $service];
    }

    // --- helpers ------------------------------------------------------------

    /** Runs $fn with the service's account ID; sets an Input error on failure. */
    private function userAction($service, callable $fn)
    {
        try {
            $row = $this->getModuleRow();
            if (!$row) {
                throw new RuntimeException(Language::_('Openplex.!error.module_row.missing', true));
            }
            $api = $this->client($row->meta);
            $fields = $this->serviceFieldsToObject($service->fields);
            $id = $this->accountId($api, $service, $fields->openplex_id ?? '', $fields->openplex_username ?? '');
            if ($id === null) {
                throw new RuntimeException(Language::_('Openplex.!error.no_account', true));
            }
            $fn($api, $id);
        } catch (Exception $e) {
            $this->Input->setErrors(['api' => ['error' => $e->getMessage()]]);
        }
        return null;
    }

    /**
     * The service's OpenPlex account ID: the one stored on creation or, for a
     * service added without the module (imported), the account with the
     * service's username and the client's e-mail address — the username alone
     * could be another customer's.
     */
    private function accountId(Client $api, $service, $id, $username)
    {
        if (ctype_digit((string) $id) && (int) $id > 0) {
            return (int) $id;
        }
        $email = '';
        if (!empty($service->client_id)) {
            Loader::loadModels($this, ['Clients']);
            $client = $this->Clients->get($service->client_id, false);
            $email = $client ? strtolower(trim((string) $client->email)) : '';
        }
        if ($username === '' || $email === '') {
            return null;
        }
        foreach ($api->get('/users') as $user) {
            if (($user['username'] ?? '') === $username && strtolower((string) ($user['email'] ?? '')) === $email) {
                return (int) $user['id'];
            }
        }
        return null;
    }

    private function serviceMeta($domain, $username, $password, $id)
    {
        return [
            ['key' => 'openplex_domain', 'value' => $domain, 'encrypted' => 0],
            ['key' => 'openplex_username', 'value' => $username, 'encrypted' => 0],
            ['key' => 'openplex_password', 'value' => $password, 'encrypted' => 1],
            ['key' => 'openplex_id', 'value' => $id, 'encrypted' => 0],
        ];
    }

    private function client($meta)
    {
        $meta = (object) $meta;
        $secure = ($meta->use_ssl ?? 'false') === 'true';
        $port = (int) ($meta->port ?? 0);
        if ($port === 0) {
            $port = $secure ? 443 : 7080;
        }
        $host = (string) ($meta->host_name ?? '');
        return new Client($host, $port, $secure, (string) ($meta->api_key ?? ''), function ($request, $response) use ($host) {
            $this->log($host . '|' . $request['method'] . ' ' . $request['path'], json_encode($request['body']), 'input', true);
            $this->log($host . '|' . $request['method'] . ' ' . $request['path'], is_string($response) ? $response : json_encode($response), 'output', true);
        });
    }

    private function meta($package, $key)
    {
        $v = $package->meta->$key ?? null;
        return $v === null || $v === '' ? self::PACKAGE_DEFAULTS[$key] : (string) $v;
    }

    private function limits($package)
    {
        return [
            'max_domains' => max(0, (int) $this->meta($package, 'max_domains')),
            'max_databases' => max(0, (int) $this->meta($package, 'max_databases')),
            'max_db_size_mb' => max(0, (int) $this->meta($package, 'max_db_size_mb')),
        ];
    }

    /**
     * A username OpenPlex accepts (3–32 characters: lowercase letters, digits,
     * "_", "." and "-", starting with a letter), from the one given or the
     * domain.
     */
    private function username($given, $domain)
    {
        foreach ([$given, explode('.', $domain)[0]] as $base) {
            $u = preg_replace('/[^a-z0-9_.-]/', '', strtolower((string) $base));
            $u = ltrim((string) $u, '0123456789_.-');
            if (strlen($u) >= 3) {
                return substr($u, 0, 32);
            }
        }
        return 'user' . random_int(10000, 99999);
    }

    public static function passwordOk($p)
    {
        return strlen((string) $p) >= 10 && preg_match('/[A-Za-z]/', $p) && preg_match('/[0-9]/', $p);
    }

    public static function generatePassword()
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $p = '';
            for ($i = 0; $i < 16; $i++) {
                $p .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (!self::passwordOk($p));
        return $p;
    }

    private function moduleRecord()
    {
        Loader::loadModels($this, ['ModuleManager']);
        $module = $this->ModuleManager->getByClass('openplex', Configure::get('Blesta.company_id'));
        return (object) ($module[0] ?? []);
    }

    private function render($view, array $vars)
    {
        $this->view = new View($view, 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'openplex' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        foreach ($vars as $k => $v) {
            $this->view->set($k, $v);
        }
        return $this->view->fetch();
    }
}
