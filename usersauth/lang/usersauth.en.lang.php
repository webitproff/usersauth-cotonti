<?php

/**
 * English language file for the "User Auth Vision" plugin.
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

// ============================================================================
// PLUGIN INFO
// ============================================================================
$L['usersauth_title'] = 'Log in as another user';
$L['usersauth_desc']  = 'Securely authenticate as another user for website and extension testing';

// ============================================================================
// UI LABELS
// ============================================================================
$L['usersauth_login_as']            = 'Log in as "{NAME}"';
$L['usersauth_login_as_short']      = 'Log in as';
$L['usersauth_return']              = 'Return to my account';
$L['usersauth_return_title']        = 'Return to administrator account';
$L['usersauth_select_user']         = 'Select a user';
$L['usersauth_search_placeholder']  = 'Username, email, or ID';
$L['usersauth_btn_login']           = 'Log in as the selected user';
$L['usersauth_btn_open_new']        = 'Open in a new tab';

// ============================================================================
// SUCCESS MESSAGES
// ============================================================================
$L['usersauth_success'] = 'You are now logged in as {NAME}';
$L['usersauth_current'] = 'You are currently logged in as: {NAME}';
$L['usersauth_help']    = 'Select a user and click the button. A new session will be created. To return to your account, use the link in the site header.';

// ============================================================================
// ERROR MESSAGES
// ============================================================================
$L['usersauth_error_not_found']       = 'User not found';
$L['usersauth_error_forbidden']       = 'You are not authorized to use this tool';
$L['usersauth_error_self']            = 'You cannot log in as yourself';
$L['usersauth_error_superadmin']      = 'Logging in as another super administrator is not allowed';
$L['usersauth_error_auth_failed']     = 'Authentication failed — please contact the administrator';
$L['usersauth_error_no_original']     = 'No original account has been saved';
$L['usersauth_error_csrf']            = 'Security error (CSRF). Refresh the page and try again';
$L['usersauth_error_session_expired'] = 'The impersonation session has expired — you have been returned to your own account';

// ============================================================================
// LOG MESSAGES
// ============================================================================
$L['usersauth_log']        = 'Administrator {ADMIN} logged in as user {USER} (ID {UID})';
$L['usersauth_log_return'] = 'Administrator returned from impersonating user {USER}';

// ============================================================================
// CONFIG LABELS
// ============================================================================
$L['cfg_count_visit']           = 'Count as a real visit';
$L['cfg_count_visit_hint']      = 'If enabled, the target user\'s lastlog, logcount, lastip, and token fields will be updated';
$L['cfg_allow_groups']          = 'Allowed groups';
$L['cfg_allow_groups_hint']     = 'Comma-separated group IDs. Empty = super administrators only (group 5)';
$L['cfg_session_lifetime']      = 'Impersonation session lifetime (sec.)';
$L['cfg_session_lifetime_hint'] = 'After this many seconds from the start of impersonation, the administrator will be automatically returned to their own account. 0 = unlimited.';