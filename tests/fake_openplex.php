<?php
// A small simulation of the OpenPlex API (/api/v1) for the tests, run with
// PHP's built-in server: php -S 127.0.0.1:7999 tests/fake_openplex.php
// State lives in $STATE_FILE so that it survives between requests.
$stateFile = getenv('OPX_FAKE_STATE') ?: sys_get_temp_dir() . '/openplex-fake.json';
$state = is_file($stateFile) ? json_decode(file_get_contents($stateFile), true) : ['users' => [], 'domains' => [], 'next' => 1, 'calls' => []];

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI']);
$path = $uri['path'];
parse_str($uri['query'] ?? '', $query);
$body = json_decode(file_get_contents('php://input') ?: 'null', true);
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

$state['calls'][] = $method . ' ' . $path . (isset($uri['query']) ? '?' . $uri['query'] : '');

function answer($status, $data)
{
    global $state, $stateFile;
    file_put_contents($stateFile, json_encode($state));
    http_response_code($status);
    header('Content-Type: application/json');
    echo $data === null ? '' : json_encode($data);
    exit;
}

$roles = ['Bearer opx_admin' => 'admin', 'Bearer opx_reseller' => 'reseller', 'Bearer opx_customer' => 'customer'];
if (!isset($roles[$auth])) {
    answer(401, ['error' => 'unauthorized', 'message' => 'invalid API key']);
}
if (strpos($path, '/api/v1/') !== 0) {
    answer(404, ['error' => 'not_found', 'message' => 'not found']);
}
$p = substr($path, strlen('/api/v1'));

if ($method === 'GET' && $p === '/auth/me') {
    answer(200, ['id' => 1, 'username' => 'admin', 'role' => $roles[$auth]]);
}
if ($method === 'GET' && $p === '/users') {
    answer(200, array_values($state['users']));
}
if ($method === 'POST' && $p === '/users') {
    foreach ($state['users'] as $u) {
        if ($u['username'] === $body['username']) {
            answer(409, ['error' => 'conflict', 'message' => 'username taken']);
        }
    }
    if (!preg_match('/^[a-z][a-z0-9_.\-]{2,31}$/', $body['username']) || strlen($body['password']) < 10) {
        answer(400, ['error' => 'invalid', 'message' => 'invalid user']);
    }
    $id = $state['next']++;
    $u = ['id' => $id, 'username' => $body['username'], 'email' => $body['email'], 'role' => $body['role'], 'locale' => $body['locale'],
        'max_domains' => $body['max_domains'], 'max_databases' => $body['max_databases'], 'max_db_size_mb' => $body['max_db_size_mb'],
        'suspended' => false, 'password' => $body['password']];
    $state['users'][(string) $id] = $u;
    unset($u['password']);
    answer(201, $u);
}
if ($method === 'POST' && $p === '/domains') {
    if (isset($state['domains'][$body['name']])) {
        answer(409, ['error' => 'conflict', 'message' => 'the domain is already on the server']);
    }
    $state['domains'][$body['name']] = $body;
    answer(201, ['id' => count($state['domains'])] + $body);
}
if (preg_match('#^/users/(\d+)(/[a-z\-]+)?$#', $p, $m)) {
    $id = $m[1];
    if (!isset($state['users'][$id])) {
        answer(404, ['error' => 'not_found', 'message' => 'user not found']);
    }
    $action = $m[2] ?? '';
    if ($method === 'PATCH' && $action === '') {
        $state['users'][$id] = array_merge($state['users'][$id], $body);
        answer(200, $state['users'][$id]);
    }
    if ($method === 'DELETE' && $action === '') {
        if (($query['purge'] ?? '') !== 'true') {
            answer(409, ['error' => 'conflict', 'message' => 'the account has websites']);
        }
        foreach ($state['domains'] as $name => $d) {
            if ((string) $d['owner_id'] === $id) {
                unset($state['domains'][$name]);
            }
        }
        unset($state['users'][$id]);
        answer(204, null);
    }
    if ($method === 'POST' && $action === '/suspend') {
        $state['users'][$id]['suspended'] = true;
        answer(204, null);
    }
    if ($method === 'POST' && $action === '/unsuspend') {
        $state['users'][$id]['suspended'] = false;
        answer(204, null);
    }
    if ($method === 'POST' && $action === '/password') {
        $state['users'][$id]['password'] = $body['password'];
        answer(204, null);
    }
    if ($method === 'POST' && $action === '/login-link') {
        if ($state['users'][$id]['suspended']) {
            answer(409, ['error' => 'suspended', 'message' => 'the account is suspended']);
        }
        answer(201, ['path' => '/login/link?link=secret' . $id, 'expires_in_seconds' => $body['expires_in_seconds']]);
    }
}
answer(404, ['error' => 'not_found', 'message' => 'no route ' . $method . ' ' . $p]);
