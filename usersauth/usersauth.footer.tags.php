<?php
declare(strict_types=1);

/* ====================
[BEGIN_COT_EXT]
Hooks=footer.tags
[END_COT_EXT]
==================== */

/**
 * User Auth Vision — return banner as a Bootstrap 5 Toast.
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

$targetName = htmlspecialchars(
    (string) ($_SESSION['usersauth_target']['name'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);

$returnUrl  = htmlspecialchars(usersauth_returnUrl(), ENT_QUOTES, 'UTF-8');
$current    = str_replace('{NAME}', $targetName, $L['usersauth_current']);
$returnText = htmlspecialchars($L['usersauth_return'], ENT_QUOTES, 'UTF-8');

$toastId = 'usersauth-return-toast';

$toast = <<<HTML
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="{$toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">User Auth Vision</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            {$current}
            <div class="mt-2">
                <a href="{$returnUrl}" class="btn btn-warning btn-sm">{$returnText}</a>
            </div>
        </div>
    </div>
</div>
HTML;

Cot::$out['footer_rc'] .= $toast;