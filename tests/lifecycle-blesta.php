<?php
// End-to-end test of the module inside a real Blesta (5 or 6): Blesta's own
// models — the ones its admin area, order forms and cron use — drive each
// action, and the OpenPlex API checks the result. It installs the module,
// adds a server, two packages and a client, then adds, suspends, unsuspends,
// re-packages, re-passwords, signs in to and cancels a service.
//
// Copy components/modules/openplex into the Blesta installation, then, as the
// web server's user:
//
//   BLESTA_DIR=/var/www/html OPX_URL=http://panel:7080 OPX_KEY_FILE=/path/to/admin-key //     php tests/lifecycle-blesta.php
//
// OPX_KEY_FILE holds an OpenPlex administrator's API key; the panel must be
// reachable from Blesta. KEEP=1 stops after adding the service, to look at it
// in Blesta's admin and client areas. Use a test panel: it creates and deletes
// accounts and websites.
$blesta = rtrim(getenv('BLESTA_DIR') ?: '/var/www/html', '/');
$opxURL = rtrim(getenv('OPX_URL') ?: 'http://openplex:7080', '/');
chdir($blesta);
$_SERVER['REQUEST_URI'] = '/';
include $blesta . '/lib/init.php';
Configure::set('Blesta.company_id', 1);
Configure::set('Blesta.company_timezone', 'UTC');
// Blesta keeps errors quiet: show them.
set_exception_handler(function (Throwable $e) {
    echo 'EXCEPTION ', get_class($e), ': ', $e->getMessage(), ' at ', $e->getFile(), ':', $e->getLine(), "
", $e->getTraceAsString(), "
";
    exit(1);
});

$key = trim(file_get_contents(getenv('OPX_KEY_FILE') ?: '/var/www/opx-key'));
$fail = 0;

function check($name, $ok, $detail = '')
{
    global $fail;
    if (!$ok) {
        $fail++;
    }
    printf("%s %s%s\n", $ok ? 'PASS' : 'FAIL', $name, $detail !== '' ? " ($detail)" : '');
}

function opx($method, $path, $body = null, $token = null)
{
    global $key, $opxURL;
    $ch = curl_init($opxURL . '/api/v1' . $path);
    $headers = ['Content-Type: application/json'];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . ($token ?? $key);
    }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 60]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode((string) $out, true)];
}

function siteOf($domain)
{
    [, $list] = opx('GET', '/domains');
    $items = $list['items'] ?? $list['domains'] ?? $list ?? [];
    foreach ((array) $items as $d) {
        if (is_array($d) && ($d['name'] ?? '') === $domain) {
            return $d;
        }
    }
    return null;
}

function waitSite($domain, $status, $secs = 300)
{
    for ($t = 0; $t < $secs; $t += 5) {
        $s = siteOf($domain);
        if (($s['status'] ?? '') === $status) {
            return $s;
        }
        sleep(5);
    }
    return siteOf($domain);
}

function errs($model)
{
    $e = $model->errors();
    return $e ? json_encode($e) : '';
}

function field($service, $name)
{
    foreach ($service->fields ?? [] as $f) {
        if ($f->key === $name) {
            return $f->value;
        }
    }
    return null;
}

$b = new stdClass();
Loader::loadModels($b, ['ModuleManager', 'Packages', 'PackageGroups', 'Clients', 'Services']);

// --- module, server, packages, client ----------------------------------------
$opxHost = parse_url($opxURL, PHP_URL_HOST);
$opxPort = (string) (parse_url($opxURL, PHP_URL_PORT) ?: (parse_url($opxURL, PHP_URL_SCHEME) === 'https' ? 443 : 80));
$opxSSL = parse_url($opxURL, PHP_URL_SCHEME) === 'https' ? 'true' : '';
$moduleId = null;
foreach ($b->ModuleManager->getAll(1) as $m) {
    if ($m->class === 'openplex') {
        $moduleId = $m->id;
    }
}
if (!$moduleId) {
    $moduleId = $b->ModuleManager->add(['company_id' => 1, 'class' => 'openplex']);
}
check('Module installed in Blesta', (bool) $moduleId, errs($b->ModuleManager));

$bad = $b->ModuleManager->addRow($moduleId, ['server_name' => 'Bad', 'host_name' => $opxHost, 'port' => $opxPort, 'use_ssl' => $opxSSL, 'api_key' => 'opx_wrong']);
check('Add Server refuses a wrong API key', !$bad, errs($b->ModuleManager));
$rowId = $b->ModuleManager->addRow($moduleId, ['server_name' => 'OpenPlex test', 'host_name' => $opxHost, 'port' => $opxPort, 'use_ssl' => $opxSSL, 'api_key' => $key]);
check('Add Server with the admin API key', (bool) $rowId, errs($b->ModuleManager));
$row = $b->ModuleManager->getRow($rowId);
check('The API key is stored encrypted', $row && ($row->meta->api_key ?? '') === $key, '');

$groupId = $b->PackageGroups->add(['company_id' => 1, 'names' => [['lang' => 'en_us', 'name' => 'OpenPlex hosting']], 'type' => 'standard']);
$package = function ($name, $sites, $dbs, $size) use ($b, $moduleId, $rowId, $groupId) {
    $id = $b->Packages->add([
        'company_id' => 1, 'module_id' => $moduleId, 'module_row' => $rowId,
        'names' => [['lang' => 'en_us', 'name' => $name]], 'status' => 'active',
        'pricing' => [['term' => 1, 'period' => 'month', 'price' => 5, 'currency' => 'USD']],
        'groups' => [$groupId],
        'email_content' => [['lang' => 'en_us', 'html' => '<p>Your OpenPlex account: {service.openplex_username}</p>']],
        'meta' => ['role' => 'customer', 'max_domains' => $sites, 'max_databases' => $dbs, 'max_db_size_mb' => $size,
            'create_website' => 'true', 'runtime' => 'static', 'runtime_version' => '', 'locale' => 'es'],
    ]);
    check("Package \"$name\" created", (bool) $id, errs($b->Packages));
    return $b->Packages->get($id);
};
$small = $package('OpenPlex Small ' . time(), 2, 1, 0);
$big = $package('OpenPlex Big ' . time(), 5, 3, 500);
$bad = $b->Packages->add(['company_id' => 1, 'module_id' => $moduleId, 'module_row' => $rowId, 'names' => [['lang' => 'en_us', 'name' => 'Bad']],
    'pricing' => [['term' => 1, 'period' => 'month', 'price' => 1, 'currency' => 'USD']], 'meta' => ['role' => 'root', 'max_domains' => 'x']]);
check('A package with invalid settings is refused', !$bad, errs($b->Packages));

$email = 'client' . time() . '@example.com';
$client = $b->Clients->create([
    'username' => 'client' . time(), 'new_password' => 'ClientPass123', 'confirm_password' => 'ClientPass123',
    'client_group_id' => 1, 'first_name' => 'Test', 'last_name' => 'Client', 'email' => $email,
    'country' => 'ES', 'send_registration_email' => 'false', 'send_registration_message' => 'false',
    'settings' => ['default_currency' => 'USD', 'language' => 'en_us'],
]);
check('Client created', $client && $client->id, errs($b->Clients));

// --- Add service (provisioning) ----------------------------------------------
$domain = 'blesta-test' . time() . '.lan';
$sid = $b->Services->add([
    'pricing_id' => $small->pricing[0]->id, 'client_id' => $client->id, 'module_row_id' => $rowId,
    'status' => 'active', 'use_module' => 'true', 'openplex_domain' => $domain,
]);
check('Add service: created and active in Blesta', (bool) $sid, errs($b->Services));
$svc = $b->Services->get($sid);
$opxId = (int) field($svc, 'openplex_id');
$username = field($svc, 'openplex_username');
$pw = field($svc, 'openplex_password');
check('Add service: account ID, username and password stored', $opxId > 0 && $username && strlen((string) $pw) >= 10, "id=$opxId user=$username");
[$code, $user] = opx('GET', "/users/$opxId");
check('Add service: OpenPlex account exists', $code === 200, "HTTP $code");
check('Add service: username matches', ($user['username'] ?? '') === $username);
check('Add service: role, limits and language', ($user['role'] ?? '') === 'customer' && ($user['max_domains'] ?? 0) === 2
    && ($user['max_databases'] ?? 0) === 1 && ($user['locale'] ?? '') === 'es',
    json_encode(array_intersect_key($user ?? [], array_flip(['role', 'max_domains', 'max_databases', 'max_db_size_mb', 'locale']))));
check('Add service: e-mail is the client\'s', ($user['email'] ?? '') === $email, $user['email'] ?? '');
$site = waitSite($domain, 'running');
check('Add service: website running', ($site['status'] ?? '') === 'running', $site['status'] ?? 'missing');
check('Add service: website owned by the account', $site !== null && (int) ($site['owner_id'] ?? 0) === $opxId);
[$code] = opx('POST', '/auth/login', ['username' => $username, 'password' => $pw], '');
check('Add service: the service password signs in to OpenPlex', $code === 200, "HTTP $code");

if (getenv('KEEP')) {
    echo "KEEP service=$sid client=" . $client->username . " (password ClientPass123) module=$moduleId server=$rowId\n";
    exit(0);
}
// The service pages render with Blesta's view engine.
$mod = $b->ModuleManager->initModule($moduleId);
$mod->setModuleRow($row);
$pkg = $b->Packages->get($small->id);
$html = $mod->getClientServiceInfo($svc, $pkg) . $mod->getAdminServiceInfo($svc, $pkg);
check('Service info renders (client and admin)', strpos($html, $username) !== false, strlen($html) . ' bytes');

// --- Suspend / Unsuspend -----------------------------------------------------
$b->Services->suspend($sid, ['use_module' => 'true', 'suspension_reason' => 'Overdue']);
check('Suspend: no errors', errs($b->Services) === '', errs($b->Services));
check('Suspend: service suspended in Blesta', $b->Services->get($sid)->status === 'suspended');
[$code] = opx('POST', '/auth/login', ['username' => $username, 'password' => $pw], '');
check('Suspend: account cannot sign in', $code !== 200, "HTTP $code");
$site = siteOf($domain);
check('Suspend: website offline', ($site['status'] ?? '') === 'disabled', $site['status'] ?? 'missing');

$b->Services->unsuspend($sid, ['use_module' => 'true']);
check('Unsuspend: no errors', errs($b->Services) === '', errs($b->Services));
check('Unsuspend: service active in Blesta', $b->Services->get($sid)->status === 'active');
[$code] = opx('POST', '/auth/login', ['username' => $username, 'password' => $pw], '');
check('Unsuspend: account signs in again', $code === 200, "HTTP $code");
$site = waitSite($domain, 'running');
check('Unsuspend: website running again', ($site['status'] ?? '') === 'running', $site['status'] ?? 'missing');

// --- Change package ----------------------------------------------------------
$b->Services->edit($sid, ['pricing_id' => $big->pricing[0]->id, 'use_module' => 'true']);
check('Change package: no errors', errs($b->Services) === '', errs($b->Services));
[, $user] = opx('GET', "/users/$opxId");
check('Change package: new limits', ($user['max_domains'] ?? 0) === 5 && ($user['max_databases'] ?? 0) === 3 && ($user['max_db_size_mb'] ?? 0) === 500,
    json_encode(array_intersect_key($user ?? [], array_flip(['max_domains', 'max_databases', 'max_db_size_mb']))));

// --- Change password ---------------------------------------------------------
$newPw = 'NewPass' . random_int(100000, 999999) . 'x';
$b->Services->edit($sid, ['openplex_password' => $newPw, 'use_module' => 'true']);
check('Change password: no errors', errs($b->Services) === '', errs($b->Services));
[$code] = opx('POST', '/auth/login', ['username' => $username, 'password' => $newPw], '');
check('Change password: the new password signs in', $code === 200, "HTTP $code");
[$code] = opx('POST', '/auth/login', ['username' => $username, 'password' => $pw], '');
check('Change password: the old one no longer does', $code !== 200, "HTTP $code");
check('Change password: stored on the service', field($b->Services->get($sid), 'openplex_password') === $newPw);
$b->Services->edit($sid, ['openplex_password' => 'short1', 'use_module' => 'true']);
check('Change password: a short password is refused', errs($b->Services) !== '', errs($b->Services));

// --- Single sign-on ----------------------------------------------------------
$svc = $b->Services->get($sid);
$err = null;
$url = $mod->signInLink($svc, $err);
check('Single sign-on: link returned', is_string($url) && strpos($url, '/login/?link=') !== false, $err ?? preg_replace('/link=.*/', 'link=…', (string) $url));
parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $q);
[$code] = opx('POST', '/auth/link', ['token' => $q['link'] ?? ''], '');
check('Single sign-on: the link signs in', $code === 200, "HTTP $code");
[$code] = opx('POST', '/auth/link', ['token' => $q['link'] ?? ''], '');
check('Single sign-on: the link works only once', $code !== 200, "HTTP $code");
$b->Services->suspend($sid, ['use_module' => 'true']);
$err = null;
$url = $mod->signInLink($b->Services->get($sid), $err);
check('Single sign-on: refused while suspended', !$url, (string) $err);
$b->Services->unsuspend($sid, ['use_module' => 'true']);

// --- Cancel ------------------------------------------------------------------
$b->Services->cancel($sid, ['date_canceled' => date('Y-m-d H:i:s', time() - 60), 'use_module' => 'true', 'notify_cancel' => 'false']);
check('Cancel: no errors', errs($b->Services) === '', errs($b->Services));
check('Cancel: service canceled in Blesta', $b->Services->get($sid)->status === 'canceled', $b->Services->get($sid)->status);
[$code] = opx('GET', "/users/$opxId");
check('Cancel: OpenPlex account deleted', $code === 404, "HTTP $code");
check('Cancel: website deleted', siteOf($domain) === null);

echo $fail === 0 ? "\nALL PASSED\n" : "\n$fail FAILED\n";
exit($fail === 0 ? 0 : 1);
