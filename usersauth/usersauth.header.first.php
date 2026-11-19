<?php
declare(strict_types=1);

/* ====================
[BEGIN_COT_EXT]
Hooks=header.first
[END_COT_EXT]
==================== */


/**
 * User Auth Vision — return banner via Cotonti notices system.
 *
 * Hook `header.first` fires before `system/header.php` renders the
 * `{HEADER_NOTICES}` block. We inject an entry into `Cot::$out['notices_array']`;
 * the core will render it as `<li><a href="URL">TITLE</a></li>`.
 *
 * Requires the theme's `header.tpl` to contain `{HEADER_NOTICES}` (or
 * `{HEADER_NOTICES_ARRAY}`) — standard for modern Cotonti themes.
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

if (usersauth_isExpired()) {
    usersauth_return();
    cot_redirect();
}

$targetName = htmlspecialchars(
    (string) ($_SESSION['usersauth_target']['name'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);

$returnUrl = usersauth_returnUrl();
$title     = str_replace('{NAME}', $targetName, $L['usersauth_current'])
           . ' — ' . $L['usersauth_return'];

// `notices_array` is an array of [url, title] pairs.
Cot::$out['notices_array'][] = [$returnUrl, $title];