<?php
declare(strict_types=1);

/* ====================
[BEGIN_COT_EXT]
Hooks=standalone
[END_COT_EXT]
==================== */


/**
 * User Auth Vision — public standalone controller.
 *
 * Handles:
 *   `index.php?e=usersauth&a=login&uid={ID}&x={TOKEN}`
 *   `index.php?e=usersauth&a=return&x={TOKEN}`
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @license BSD
 */


defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('usersauth', 'plug');
require_once cot_langfile('usersauth', 'plug');

$action = (string) cot_import('a', 'G', 'ALP');
$uid    = (int) cot_import('uid', 'G', 'INT');

// Quick rejection of requests without a valid XSS token. The same check is
// repeated inside `usersauth_loginAs()` / `usersauth_return()` as defence in
// depth, but failing here avoids loading the full business logic.
cot_check_xg();

if ($action === 'return') {
    $result = usersauth_return();

    if (!empty($result['error'])) {
        $key = 'usersauth_error_' . $result['error'];
        cot_error($L[$key] ?? $L['Error']);
        cot_redirect(cot_url('index'));
    }

    cot_message($L['usersauth_return']);
    cot_redirect(cot_url('index'));
}

if ($action === 'login' && $uid > 0) {
    if (!usersauth_isAllowed()) {
        cot_die_message(403, true);
    }

    $result = usersauth_loginAs($uid);

    if (!empty($result['error'])) {
        $key = 'usersauth_error_' . $result['error'];
        cot_error($L[$key] ?? $L['Error']);
        cot_redirect(cot_url('users', ['m' => 'details', 'id' => $uid]));
    }

    // Only internal paths are accepted; anything else falls back to index.
    $rawRedirect = (string) cot_import('redirect', 'G', 'TXT');
    $target      = usersauth_safeRedirect($rawRedirect);

    // Name comes from the DB — escape before injecting into the message.
    $userName = htmlspecialchars((string) $result['user']['user_name'], ENT_QUOTES, 'UTF-8');

    // `str_replace()` is used because `cot_rc()` does not expand `{...}`
    // placeholders when given a plain `$L[...]` string (only when given a key
    // from the `$R` array).
    cot_message(str_replace('{NAME}', $userName, $L['usersauth_success']));
    cot_redirect($target);
}

// Any other action — bounce to the home page.
cot_redirect(cot_url('index'));