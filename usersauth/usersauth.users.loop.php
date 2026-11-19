<?php
declare(strict_types=1);
/* ====================
[BEGIN_COT_EXT]
Hooks=users.loop
[END_COT_EXT]
==================== */

/**
 * User Auth Vision — login-as button in the users list loop.
 *
 * Hook: `users.loop`.
 *
 * Same conditions as `usersauth.users.details.tags.php`: viewer must be
 * allowed, the row must not be the viewer's own profile, and the user ID must
 * be valid.
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('usersauth', 'plug');
require_once cot_langfile('usersauth', 'plug');

if (!usersauth_isAllowed()) {
    return;
}

$targetId = (int) ($urr['user_id'] ?? 0);

if ($targetId < 1 || $targetId === (int) (Cot::$usr['id'] ?? 0)) {
    return;
}

$url  = usersauth_publicUrl($targetId);
$name = (string) ($urr['user_name'] ?? '');
$safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

$shortText = (string) $L['usersauth_login_as_short'] . ' «' . $safe . '»';

// `str_replace()` is used because `cot_rc()` does not expand `{...}`
// placeholders for plain `$L` strings.
$fullTitle = str_replace('{NAME}', $safe, $L['usersauth_login_as']);

$t->assign([
    'USERS_ROW_USERSAUTH_URL'  => $url,
    'USERS_ROW_USERSAUTH_LINK' => cot_rc_link(
        $url,
        $shortText,
        [
            'class'  => 'btn btn-sm btn-outline-danger usersauth-login-as',
            'title'  => $fullTitle,
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ]
    ),
    'USERS_ROW_USERSAUTH_ICON' => cot_rc_link(
        $url,
        '<i class="fa-solid fa-right-to-bracket"></i>',
        [
            'class'  => 'btn btn-sm btn-danger',
            'title'  => $fullTitle,
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ]
    ),
]);