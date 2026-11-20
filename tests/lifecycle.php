<?php
// Runs the module's methods the way Blesta calls them, against the OpenPlex
// API simulation (tests/fake_openplex.php) on 127.0.0.1:$OPX_PORT:
//
//   php -S 127.0.0.1:7999 tests/fake_openplex.php &
//   OPX_PORT=7999 php tests/lifecycle.php
//
// The same script also runs against a real panel with OPX_KEY set to an
// administrator's API key (it then uses that key for every "admin" call).
require __DIR__ . '/blesta_stubs.php';
require __DIR__ . '/../components/modules/openplex/openplex.php';

$port = getenv('OPX_PORT') ?: '7999';
$adminKey = getenv('OPX_KEY') ?: 'opx_admin';
$fails = 0;
function check($what, $ok, $detail = '')
{
    global $fails;
    if (!$ok) {
        $fails++;
    }
    printf("%s %s%s\n", $ok ? 'ok  ' : 'FAIL', $what, $detail === '' ? '' : '  — ' . (is_string($detail) ? $detail : json_encode($detail)));
}

class TestOpenplex extends Openplex
{
    public $redirected;
    protected function redirect($url) { $this->redirected = $url; }
}

function fields(array $meta)
{
    return array_map(function ($m) { return (object) ['key' => $m['key'], 'value' => $m['value']]; }, $meta);
}

$clients = new FakeClients();
$clients->emails[7] = 'blesta-test@example.com';
Loader::$clients = $clients;

$row = (object) ['id' => 1, 'meta' => (object) ['server_name' => 'Test', 'host_name' => '127.0.0.1', 'port' => $port, 'use_ssl' => 'false', 'api_key' => $adminKey]];
$m = new TestOpenplex();
$m->row = $row;

// Servers: the API key is checked (customer keys refused), stored encrypted.
$vars = ['server_name' => 'Main', 'host_name' => '127.0.0.1', 'port' => $port, 'use_ssl' => '', 'api_key' => $adminKey];
$meta = $m->addModuleRow($vars);
$byKey = $meta ? array_column($meta, null, 'key') : [];
check('addModuleRow with an admin key', $meta !== null && $byKey['api_key']['encrypted'] === 1 && $byKey['use_ssl']['value'] === 'false', $m->Input->errors());
if (getenv('OPX_KEY') === false) {
    $vars['api_key'] = 'opx_customer';
    check('addModuleRow refuses a customer key', $m->addModuleRow($vars) === null && isset($m->Input->errors()['api_key']['connection']));
    $vars['api_key'] = 'opx_wrong';
    check('addModuleRow refuses a wrong key', $m->addModuleRow($vars) === null);
}
$vars = ['server_name' => 'Main', 'host_name' => 'not a host!', 'port' => $port, 'api_key' => $adminKey];
check('addModuleRow refuses a bad hostname', $m->addModuleRow($vars) === null && isset($m->Input->errors()['host_name']));
$vars = ['server_name' => 'HTTPS', 'host_name' => 'panel.example.com', 'port' => '', 'use_ssl' => 'true', 'api_key' => ''];
$m->addModuleRow($vars);
check('an empty port defaults to 443 with HTTPS', $vars['port'] === '443');

// Packages.
check('getPackageFields has 8 settings', count($m->getPackageFields()->fields) === 8);
$pkgMeta = $m->addPackage(['meta' => ['role' => 'customer', 'max_domains' => '2', 'max_databases' => '3', 'max_db_size_mb' => '50',
    'create_website' => 'true', 'runtime' => 'static', 'runtime_version' => '', 'locale' => 'es']]);
check('addPackage', is_array($pkgMeta) && count($pkgMeta) === 8, $m->Input->errors());
check('addPackage refuses a bad limit', $m->addPackage(['meta' => ['role' => 'customer', 'max_domains' => '-1', 'max_databases' => '1',
    'max_db_size_mb' => '0', 'runtime' => 'php', 'locale' => '']]) === null);
check('addPackage stores an unchecked box as false', array_column($m->addPackage(['meta' => ['role' => 'reseller', 'max_domains' => '0',
    'max_databases' => '0', 'max_db_size_mb' => '0', 'runtime' => 'php', 'locale' => '']]), 'value', 'key')['create_website'] === 'false');
$package = (object) ['id' => 1, 'meta' => (object) array_column($pkgMeta, 'value', 'key')];

// Order form fields.
check('getClientAddFields asks for the domain', $m->getClientAddFields($package, (object) ['domain' => 'x.test'])->fields[0]->attached[0]->value === 'x.test');
check('getAdminAddFields', count($m->getAdminAddFields($package)->fields) === 4);
check('a website package needs a domain', !$m->validateService($package, ['openplex_domain' => '']));
check('a bad password is refused', !$m->validateService($package, ['openplex_domain' => 'a.test', 'openplex_password' => 'short']));

// Create: username from the domain, generated password, website.
$add = ['client_id' => 7, 'use_module' => 'true', 'openplex_domain' => 'Blesta-Test.LAN', 'openplex_username' => '', 'openplex_password' => ''];
$meta = $m->addService($package, $add);
check('addService', is_array($meta), $m->Input->errors());
$f = array_column($meta ?: [], 'value', 'key');
check('the username comes from the domain', ($f['openplex_username'] ?? '') === 'blesta-test', $f);
check('a password was generated and is stored encrypted', Openplex::passwordOk($f['openplex_password'] ?? '')
    && array_column($meta, 'encrypted', 'key')['openplex_password'] === 1);
check('the account ID is stored', ctype_digit($f['openplex_id'] ?? ''));
check('no secret in the logs', strpos(json_encode($m->logs), $f['openplex_password'] ?? 'x') === false && strpos(json_encode($m->logs), $adminKey) === false);
$service = (object) ['id' => 10, 'client_id' => 7, 'status' => 'active', 'module_row_id' => 1, 'fields' => fields($meta)];

$api = new OpenPlex\Blesta\Client('127.0.0.1', $port, false, $adminKey);
$user = null;
foreach ($api->get('/users') as $u) {
    if ((string) $u['id'] === $f['openplex_id']) {
        $user = $u;
    }
}
check('the account has the package limits and the client e-mail', $user && $user['max_domains'] === 2 && $user['max_databases'] === 3
    && $user['max_db_size_mb'] === 50 && $user['email'] === 'blesta-test@example.com' && $user['role'] === 'customer', $user);

// A second order for the same domain name: another username, website refused
// but the account stays.
$meta2 = $m->addService($package, $add);
$f2 = array_column($meta2 ?: [], 'value', 'key');
check('a taken username gets digits', preg_match('/^blesta-test\d{3}$/', $f2['openplex_username'] ?? '') === 1, $f2);
check('a refused website keeps the account', ctype_digit($f2['openplex_id'] ?? '') && strpos(json_encode($m->logs), 'not its website') !== false);
$service2 = (object) ['id' => 11, 'client_id' => 7, 'status' => 'active', 'module_row_id' => 1, 'fields' => fields($meta2)];

// Without the module: only recorded.
$meta3 = $m->addService($package, ['use_module' => 'false', 'openplex_domain' => 'manual.test', 'openplex_id' => '999']);
check('addService without the module records the given ID', array_column($meta3 ?: [], 'value', 'key')['openplex_id'] === '999');

// Suspend, unsuspend, change package, password.
$m->Input->setErrors([]);
$m->suspendService($package, $service);
check('suspendService', !$m->Input->errors(), $m->Input->errors());
$m->redirected = null;
$m->tabClientLogin($package, $service, [], ['openplex_login' => '1']);
check('a suspended account can\'t be signed in', $m->redirected === null);
$m->unsuspendService($package, $service);
check('unsuspendService', !$m->Input->errors(), $m->Input->errors());

$bigger = (object) ['meta' => (object) (array_column($pkgMeta, 'value', 'key') + []) ];
$bigger->meta->max_domains = '5';
$bigger->meta->role = 'reseller';
$m->changeServicePackage($package, $bigger, $service);
foreach ($api->get('/users') as $u) {
    if ((string) $u['id'] === $f['openplex_id']) {
        $user = $u;
    }
}
check('changeServicePackage', !$m->Input->errors() && $user['max_domains'] === 5 && $user['role'] === 'reseller', $user);

$edited = $m->editService($package, $service, ['use_module' => 'true', 'openplex_password' => 'NewPassword123']);
check('editService changes the password', is_array($edited) && array_column($edited, 'value', 'key')['openplex_password'] === 'NewPassword123', $m->Input->errors());
check('editService refuses a short password', $m->editService($package, $service, ['use_module' => 'true', 'openplex_password' => 'short']) === null);
$service->fields = fields($edited);

// Single sign-on.
$m->redirected = null;
$html = @$m->tabClientLogin($package, $service, [], []);
check('the login tab shows its button without signing in', $m->redirected === null);
$m->tabClientLogin($package, $service, [], ['openplex_login' => '1']);
check('the client login tab redirects to a one-time link', strpos((string) $m->redirected, 'http://127.0.0.1:' . $port . '/login/link?link=') === 0, $m->redirected);
$m->redirected = null;
$m->tabLogin($package, $service, [], ['openplex_login' => '1']);
check('the admin login tab too', $m->redirected !== null);
$inactive = clone $service;
$inactive->status = 'suspended';
$m->redirected = null;
$m->tabClientLogin($package, $inactive, [], ['openplex_login' => '1']);
check('an inactive service is not signed in', $m->redirected === null);
check('sign-in links are kept out of the logs', strpos(json_encode($m->logs), 'link=secret') === false);

// An imported service: found by username and the client's e-mail.
$imported = (object) ['id' => 12, 'client_id' => 7, 'status' => 'active', 'module_row_id' => 1,
    'fields' => fields([['key' => 'openplex_username', 'value' => $f['openplex_username']], ['key' => 'openplex_id', 'value' => '']])];
$m->Input->setErrors([]);
$m->suspendService($package, $imported);
check('an imported service is matched by username and e-mail', !$m->Input->errors(), $m->Input->errors());
$m->unsuspendService($package, $imported);
$clients->emails[8] = 'someone-else@example.com';
$imported->client_id = 8;
$m->suspendService($package, $imported);
check('…but not another client\'s account with the same username', (bool) $m->Input->errors());
$m->Input->setErrors([]);

// Cancel: deleted with its websites; a second cancel is fine.
$after = $m->cancelService($package, $service);
check('cancelService', !$m->Input->errors() && array_column($after ?: [], 'value', 'key')['openplex_id'] === '', $m->Input->errors());
$gone = true;
foreach ($api->get('/users') as $u) {
    if ((string) $u['id'] === $f['openplex_id']) {
        $gone = false;
    }
}
check('the account is gone', $gone);
$service->fields = fields($after);
$m->cancelService($package, $service);
check('cancelling again does nothing', !$m->Input->errors());
$m->cancelService($package, $service2);

// The pages render.
check("selectModuleRow takes the group's first server", $m->selectModuleRow(9) === 4);
$module = (object) ['id' => 3, 'name' => 'OpenPlex', 'rows' => [$row], 'groups' => [(object) ['id' => 1, 'name' => 'G', 'rows' => [$row]]]];
$empty = [];
check('manage page', strpos($m->manageModule($module, $empty), '127.0.0.1') !== false);
check('add server page', strpos($m->manageAddRow($empty), 'openplex_api_key') !== false);
$filled = [];
$m->manageEditRow($row, $filled);
check('edit server page keeps the saved settings', ($filled['host_name'] ?? '') === '127.0.0.1');
$svc = (object) ['id' => 20, 'client_id' => 7, 'status' => 'active', 'module_row_id' => 1, 'fields' => fields([['key' => 'openplex_username', 'value' => 'ana'], ['key' => 'openplex_domain', 'value' => 'ana.test'], ['key' => 'openplex_id', 'value' => '5']])];
check('client service info', strpos($m->getClientServiceInfo($svc, $package), 'ana.test') !== false);
check('admin service info', strpos($m->getAdminServiceInfo($svc, $package), '#5') !== false);

// Every language string used exists, in both languages.
// Blesta's own strings (AdminCompanyModules…, AppController…) are Blesta's.
$ours = array_filter(Language::$missing, function ($k) { return strpos($k, 'Openplex.') === 0; });
check('every string the module used exists', !$ours, array_unique($ours));
$used = [];
foreach (array_merge([__DIR__ . '/../components/modules/openplex/openplex.php', __DIR__ . '/../components/modules/openplex/config.json'],
    glob(__DIR__ . '/../components/modules/openplex/views/default/*.pdt')) as $file) {
    preg_match_all('/[\'"](Openplex\.[A-Za-z0-9_.!\[\]]+)[\'"]/', file_get_contents($file), $mm);
    $used = array_merge($used, $mm[1]);
}
foreach (['en_us', 'es_es'] as $code) {
    $lang = [];
    require __DIR__ . '/../components/modules/openplex/language/' . $code . '/openplex.php';
    $missing = array_diff(array_unique($used), array_keys($lang));
    $missing = array_filter($missing, function ($k) { return !in_array(substr($k, -1), ['.', '['], true); });
    check("every string in the code is in $code", !$missing, array_values($missing));
    if ($code === 'es_es') {
        check('es_es has no extra strings', !array_diff(array_keys($lang), array_keys(Language::$lang)), array_values(array_diff(array_keys($lang), array_keys(Language::$lang))));
        check('es_es has every en_us string', !array_diff(array_keys(Language::$lang), array_keys($lang)), array_values(array_diff(array_keys(Language::$lang), array_keys($lang))));
    }
}

echo $fails === 0 ? "\nall passed\n" : "\n$fails failed\n";
exit($fails === 0 ? 0 : 1);
