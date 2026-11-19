<?php
declare(strict_types=1);

/* ====================
[BEGIN_COT_EXT]
Hooks=header.user.tags
[END_COT_EXT]
==================== */

/**
 * User Auth Vision — site header banner.
 *
 * Renders a "you are currently under {NAME}" banner with a return link while
 * an impersonation session is active.
 *
 * Hook: `header.user.tags`.
 *
 * IMPORTANT: this hook is included by `cot_getextplugins()` only when the
 * *current* user has the `R` bit on `plug.usersauth`. The setup file therefore
 * declares `Auth_members=R` — otherwise the banner would not render for the
 * impersonated user and the administrator would be locked inside the target
 * account.
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('usersauth', 'plug');
require_once cot_langfile('usersauth', 'plug');

if (!usersauth_isImpersonating()) {
    return;
}

// Auto-return when the impersonation session has expired.
if (usersauth_isExpired()) {
    usersauth_return();
    cot_redirect();
}

$returnUrl  = usersauth_returnUrl();
$targetName = htmlspecialchars((string) ($_SESSION['usersauth_target']['name'] ?? ''), ENT_QUOTES, 'UTF-8');

$t->assign([
    'HEADER_USERSAUTH_RETURN_URL'  => $returnUrl,
    'HEADER_USERSAUTH_RETURN_LINK' => cot_rc_link(
        $returnUrl,
        $L['usersauth_return'],
        [
            'class'          => 'btn btn-warning btn-sm',
            'title'          => $L['usersauth_return_title'],
            'rel'            => 'noopener noreferrer',
            'data-usersauth' => 'return',
        ]
    ),
    // `str_replace()` because `cot_rc()` does not expand `{...}` placeholders
    // for plain `$L` strings.
    'HEADER_USERSAUTH_CURRENT' => str_replace('{NAME}', $targetName, $L['usersauth_current']),
]);