<?php
declare(strict_types=1);


/* ====================
[BEGIN_COT_EXT]
Hooks=users.details.tags
[END_COT_EXT]
==================== */

/**
 * User Auth Vision — login-as button on the user details page.
 *
 * Hook: `users.details.tags`.
 *
 * The button is only rendered when:
 *   - the viewer is allowed to use the tool;
 *   - the viewed profile is not the viewer's own;
 *   - the viewed profile has a valid user ID.
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

// `str_replace()` is used because `cot_rc()` does not expand `{...}`
// placeholders for plain `$L` strings.
$linkText = str_replace('{NAME}', $safe, $L['usersauth_login_as']);

$t->assign([
    'USERS_DETAILS_USERSAUTH_URL'  => $url,
    'USERS_DETAILS_USERSAUTH_LINK' => cot_rc_link(
        $url,
        $linkText,
        [
            'class'  => 'btn btn-danger btn-sm usersauth-login-as',
            'title'  => $linkText,
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ]
    ),
    'USERS_DETAILS_USERSAUTH_ICON' => cot_rc_link(
        $url,
        '<i class="fa-solid fa-right-to-bracket"></i>',
        [
            'class'             => 'btn btn-danger rounded-2',
            'title'             => $linkText,
            'target'            => '_blank',
            'rel'               => 'noopener noreferrer',
            'data-bs-toggle'    => 'tooltip',
            'data-bs-placement' => 'top',
        ]
    ),
]);