# User Auth Vision for Cotonti

A plugin for the Cotonti CMF that allows a super administrator (and, optionally, users of allowed groups) to securely log in as any user account for testing, permission verification, template debugging, and site behavior scenarios — with the ability to return to their own account with a single action.

[![Version](https://img.shields.io/badge/version-2.2.1-green.svg)](https://github.com/webitproff/usersauth-cotonti)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti-v.1-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple.svg)](https://www.php.net/releases/8_5_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/usersauth-cotonti/blob/main/LICENSE)

---  
<img width="1672" height="941" alt="usersauth-cotonti" src="https://github.com/user-attachments/assets/11f45b5f-9ca9-4fa5-bb91-023edcf686b3" />

---  


- **Repository:** https://github.com/webitproff/usersauth-cotonti
- **Marketplace:** https://abuyfile.com/market/cotonti/plugs/usersauth
- **Support:** https://abuyfile.com/forums/cotonti/custom/
- **Cotonti CMF:** https://github.com/Cotonti/Cotonti

**Version:** 2.2.1  
**Date:** 2026-11-19  
**Author:** [webitproff](https://github.com/webitproff)  
**License:** BSD

---

## Table of Contents

1. [Purpose](#1-purpose)
2. [Key Features](#2-key-features)
3. [Requirements](#3-requirements)
4. [Installation](#4-installation)
5. [Plugin Settings](#5-plugin-settings)
6. [Business Logic and Algorithms](#6-business-logic-and-algorithms)
7. [Cotonti Hooks](#7-cotonti-hooks)
8. [Entry Points (URL Routes)](#8-entry-points-url-routes)
9. [Plugin API](#9-plugin-api)
10. [Standard Cotonti Functions Used by the Plugin](#10-standard-cotonti-functions-used-by-the-plugin)
11. [Session Variables](#11-session-variables)
12. [Security Model](#12-security-model)
13. [Access Rights](#13-access-rights)
14. [Logging](#14-logging)
15. [Theme Template Integration](#15-theme-template-integration)
16. [User Guide](#16-user-guide)
17. [File Structure](#17-file-structure)
18. [Troubleshooting](#18-troubleshooting)
19. [Glossary](#19-glossary)
20. [License and Links](#20-license-and-links)

---

## 1. Purpose

When developing and maintaining a Cotonti-based site, it is often necessary to see the site through another user's eyes — a regular member, an author in a specific group, a moderator — in order to verify permissions, templates, section accessibility, and interactive elements. Standard Cotonti tools do not allow this: changing a password, manually logging in under someone else's account, or creating a test user with the same groups is inconvenient and risky.

The **User Auth Vision** plugin solves this task:

- Provides the administrator with a unified interface for finding the desired user.
- Performs **full authorization** on their behalf — identical to a regular login (cookie, `sid`, `user_token`, XSS token).
- Provides **instant return** to the original administrator account via a single link, without entering a password.
- Allows **not counting** the visit in the target user's statistics — so as not to distort their metrics.
- Writes all actions to the Cotonti log.
- Does not store any "bypass" mechanisms — uses the standard `cot_user_authorize()`, so all rights, groups, hooks, plugins, and themes work the same as with a real login.

The plugin **has no tables of its own** in the database and works only with the standard `cot_users`, `cot_auth`, `cot_core`, `cot_config`, `cot_log`.

---

## 2. Key Features

- ✅ Login as any user from the admin panel: search by username, e-mail, or ID.
- ✅ Login as a user from their profile (page `users.details`).
- ✅ Login as a user from the general user list (loop `users.loop`).
- ✅ **Three independent display options for the return banner** — the administrator enables the one that suits their theme:
  - **A. Site header** via the `header.user.tags` hook — an `alert-warning`-style strip with `{HEADER_USERSAUTH_*}` tags.
  - **B. Cotonti notices block** via the `header.first` hook — a line inside `{HEADER_NOTICES}`, no template edits required.
  - **C. Bootstrap 5 Toast** via the `footer.tags` hook — a strip in the lower-right corner, independent of the header.
- ✅ Return to the original administrator account via a single link.
- ✅ Configurable list of groups allowed to use the tool (by default — super administrators only).
- ✅ Prohibition of logging in as a super administrator for non-super administrators.
- ✅ Prohibition of logging in as oneself.
- ✅ "Do not count visit" option: restores `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip`, and `user_token` of the target user to their previous values.
- ✅ **Impersonation time limit** (`session_lifetime`): upon expiration, the session automatically returns to the original account.
- ✅ **Forced logout** on failed return — the administrator does not get "stuck" under someone else's account if the original account is banned/deleted.
- ✅ **CSRF protection** inside the `loginAs()` / `return()` functions themselves — even when called from third-party code.
- ✅ **Open Redirect protection** via `usersauth_safeRedirect()` — only internal paths are allowed.
- ✅ **XSS protection** via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` for all names output into templates.
- ✅ **LIKE injection and DoS protection** in search via `LOCATE()`.
- ✅ **Tab-napping and referrer-leak protection** via `rel="noopener noreferrer"` on all `target="_blank"` links.
- ✅ **Log sanitization** via `usersauth_sanitizeForLog()`.
- ✅ **Default rights are balanced**: `Auth_guests=R`, `Auth_members=R` — the minimum required for the banner hook to trigger for the impersonated user. The `A` right on the plugin is not automatically granted to anyone — only explicitly via **Admin → Rights**.
- ✅ **Strict typing** (`declare(strict_types=1)`) in all files.
- ✅ **Filtering of system groups** (0/1/2/3) in `allow_groups`.
- ✅ Compatibility with Bootstrap 5 and Font Awesome.
- ✅ No modifications to the Cotonti core required.

---

## 3. Requirements

| Component | Requirement |
|---|---|
| CMS | Cotonti CMF v1.x |
| PHP | **8.5** and higher (`declare(strict_types=1)`, `str_starts_with`, `str_contains`, typed signatures, `random_bytes`) |
| MySQL | **8.4** and higher (uses `CAST`, `LIMIT`, `LOCATE`, prepared statements) |
| Module `users` | Required (`Requires_modules=users` in the setup file) |
| Extension `mbstring` | Required (standard Cotonti requirement) |
| Font Awesome (optional) | For the `fa-solid fa-right-to-bracket` icon in buttons |
| Bootstrap 5 (optional) | For option C (Toast), as well as for styling buttons (classes `btn`, `alert`, `form-control`) |

The plugin **does not create its own tables**. It uses the standard ones:

- `cot_users` — target accounts, restoration of visit fields and `user_token`;
- `cot_auth` — group rights on the plugin (`auth_code='plug'`, `auth_option='usersauth'`);
- `cot_core` — plugin registration (`ct_code='usersauth'`);
- `cot_config` — settings (`config_cat='usersauth'`);
- `cot_log` — records of login and return.

---

## 4. Installation

### 4.1. Installation via Cotonti

1. Download the plugin archive from the repository or marketplace.
2. Upload to the server (FTP/SSH). Unpack the archive. Inside there will be a folder named after the plugin (in our case, `usersauth`). Copy this folder to the `/plugins/` directory on the server — via an FTP client, SSH, or the hosting's file manager. The resulting path should be: `plugins/usersauth/` (inside — `usersauth.setup.php` and other files).
3. Installation via the admin panel. After uploading the files, go to the Cotonti admin panel → **Extensions** section (`admin.php?m=extensions`). The line `usersauth` will appear in the list of extensions. Click **Install**. The plugin does not create its own tables, but it is recommended to back up the database.
4. Configuration. After installation, go to **Extensions → usersauth → Settings** and set the parameters.
5. Rights. Everything is already configured during installation — do not touch anything.

Then in the admin panel: **Extensions → usersauth → Install**.

### 4.3. What Happens During Installation

1. `usersauth.setup.php` is read (block `BEGIN_COT_EXT`).
2. The dependency `Requires_modules=users` is checked.
3. The directory `plugins/usersauth/` is scanned; each file `usersauth.*.php`, except `setup`, `install`, `uninstall`, `configure`, is read as a hook file, and the `Hooks` field is taken from the `BEGIN_COT_EXT` block.
4. A row is written to `cot_core`: `ct_code='usersauth'`, `ct_version='2.2.1'`, `ct_plug=1`.
5. All hooks are written to `cot_plugins`.
6. Rights for groups are written to `cot_auth` according to `Auth_guests`, `Lock_guests`, `Auth_members`, `Lock_members`.
7. Default setting values are written to `cot_config`: `count_visit='0'`, `allow_groups=''`, `session_lifetime='3600'`.

There are no SQL scripts or PHP installation handlers — the `setup/` directory is absent.

### 4.4. Removal

Via **Extensions → usersauth → Uninstall**. Cotonti:

- removes records from `cot_plugins` with `pl_code='usersauth'`;
- removes records from `cot_auth` with `auth_code='plug'` and `auth_option='usersauth'`;
- removes records from `cot_config` with `config_cat='usersauth'`;
- removes the record from `cot_core` with `ct_code='usersauth'`.

Plugin files are **not removed** automatically — they must be deleted from `plugins/usersauth/` manually. Tables `cot_users`, `cot_log` are not affected.

---

## 5. Plugin Settings

Stored in `Cot::$cfg['plugin']['usersauth']`. Available at:

- **Admin → Extensions → usersauth → Settings**
- directly: `admin.php?m=config&n=edit&o=plug&p=usersauth`

| # | Key | Type | Default | Values |
|---|---|---|---|---|
| 01 | `count_visit` | radio | `0` | `0` / `1` |
| 03 | `allow_groups` | text | (empty) | Group IDs separated by commas |
| 04 | `session_lifetime` | string | `3600` | integer ≥ 0 (seconds) |

### 5.1. `count_visit` — Count as a Real Visit

- **What it does:** if enabled (`1`), upon logging in as a user, their `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip`, and `user_token` fields are updated as if the user had logged in themselves.
- **If disabled (`0`, default):** the plugin takes the current values of these fields **before** `cot_user_authorize()` and **restores** them after authorization. The administrator's visit does not appear in the user's statistics, does not reset the counter, does not "expose" the administrator's last IP, and does not rotate the CSRF token of the user's open tabs.
- **Why:** for testing, this is the correct behavior — you should not distort the user's statistics. Enable `count_visit=1` only if you are testing plugins that read `user_lastlog` / `user_lastvisit` / `user_logcount` / `user_lastip`, or if you want to "count" a real visit.

### 5.2. `allow_groups` — Allowed Groups

- **What it does:** a list of Cotonti group IDs whose users can use the plugin, in addition to super administrators (`COT_GROUP_SUPERADMINS`, usually `5`).
- **Format:** comma-separated string, for example `4,6`.
- **Empty (`''`):** the plugin is available only to super administrators.
- **System groups are ignored:** values `0` (Default), `1` (Guests), `2` (Inactive), `3` (Banned) are discarded even if specified. This protects against accidentally granting the tool to guests/banned users.
- **Additionally,** the group must receive the `A` right on `plug.usersauth` in **Admin → Rights → usersauth** (see section 13). Without this, access to the admin page will be denied before `usersauth_isAllowed()` even runs.

### 5.3. `session_lifetime` — Impersonation Session Lifetime

- **What it does:** sets how many seconds after the start of impersonation the administrator will be automatically returned to their own account.
- **Default:** `3600` (1 hour).
- **`0`:** no time limit (the session lives until manual return or until the Cotonti cookie expires).
- **How the check works:**
  - on the first login, the current `time()` is written into `$_SESSION['usersauth_original']['started_at']`;
  - `usersauth_isExpired()` is called on the admin page (`usersauth.tools.php`) and in the selected banner hook — `header.first` or `header.user.tags`. For option C (`footer.tags`), the check is not performed: at the time the footer is rendered, headers have already been sent, so `cot_redirect()` is not possible;
  - if `time() − started_at > session_lifetime` and `session_lifetime > 0` — the plugin automatically calls `usersauth_return()` and redirects to the original account;
  - in the admin panel, on expiration, the message `usersauth_error_session_expired` is shown.

Example: `session_lifetime=1800` — after 30 minutes the administrator is automatically returned to their own account.

---

## 6. Business Logic and Algorithms

### 6.1. General Flow Diagram

```
┌──────────────────────────────────────────────────────────────┐
│ Login to the admin panel or click a button on the frontend   │
└──────────────────────────────────┬───────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────┐
│ usersauth_verifyCsrf() — XSS token check inside the function │
│   └─ invalid → ['error' => 'csrf']                           │
└──────────────────────────────────┬───────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────┐
│ usersauth_isAllowed()                                        │
│   ├─ guest → false                                           │
│   ├─ superadmin → true                                       │
│   └─ allow_groups (without system) ∩ user.groups → t/f       │
└──────────────────────────────────┬───────────────────────────┘
                                   │ true
                                   ▼
┌──────────────────────────────────────────────────────────────┐
│ usersauth_loginAs($uid, $countVisit=null)                    │
│   1. check target_id, self, user existence                   │
│   2. cot_fillGroupsForUser($user)                            │
│   3. prohibit non-superadmin → superadmin                    │
│   4. save original + started_at in $_SESSION                 │
│   5. save target in $_SESSION                                │
│   6. snapshot of visit fields + user_token                   │
│   7. cot_user_authorize($user, true)                         │
│   8. if count_visit=0 — restore the snapshot                 │
│   9. cot_log() with sanitization                             │
└──────────────────────────────────┬───────────────────────────┘
                                   │
                                   ▼
                User is authorized as target
              Return banner appears in the selected location
                 (header / notices / Bootstrap Toast)

┌──────────────────────────────────────────────────────────────┐
│ Auto-return: usersauth_isExpired() → usersauth_return()      │
│   triggered in the user entry-point or in the selected       │
│   banner hook                                                │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│ Manual return: usersauth_return()                            │
│   1. usersauth_verifyCsrf()                                  │
│   2. $_SESSION['usersauth_original']['id'] exists?           │
│   3. load original from DB                                   │
│      └─ not found → clearSession + unset → not_found         │
│   4. cot_user_authorize($original, true)                     │
│   5. unset session keys                                      │
│   6. on authorization error → clearSession → auth_failed     │
│   7. cot_log() with sanitization                             │
└──────────────────────────────────────────────────────────────┘
```

### 6.2. `usersauth_verifyCsrf()` — Internal CSRF Check

```php
function usersauth_verifyCsrf(): bool
{
    $token    = (string) cot_import('x', 'G', 'ALP');
    $expected = (string) (Cot::$sys['xk'] ?? '');

    return $expected !== '' && hash_equals($expected, $token);
}
```

**Logic:**

1. Imports the `x` parameter from GET (`cot_import('x', 'G', 'ALP')`).
2. Compares it with `Cot::$sys['xk']` via `hash_equals()` — a timing-safe comparison that is not susceptible to timing attacks.
3. Returns `false` if the expected token is empty.

**Where it is called:** inside `usersauth_loginAs()` and `usersauth_return()` — **as the first line**, before any rights checks. This is defence-in-depth for cases when the functions are called from third-party code bypassing the entry points (`usersauth.php` / `usersauth.tools.php`, where `cot_check_xg()` is also present).

### 6.3. `usersauth_isAllowed()` — Permission Check for Tool Usage

```php
function usersauth_isAllowed(): bool
```

**Step-by-step logic:**

1. `Cot::$usr['id']` is cast to `int`; if `< 1` → `false` (guest).
2. `Cot::$usr['groups']` is cast to an array of `int`; empty and non-numeric values are discarded.
3. If the array is empty → `false`.
4. If `COT_GROUP_SUPERADMINS` is in the array → `true` (super administrator always passes).
5. Reads `Cot::$cfg['plugin']['usersauth']['allow_groups']`; empty → `false`.
6. Splits by `,`, casts to `int`, filters out zero values.
7. **Discards system groups** `0`, `1`, `2`, `3` — this protects against misconfiguration.
8. If the array is empty after filtering → `false`.
9. Returns `true` if the intersection of the allowed groups and the user's groups is non-empty.

**Where it is used:**

| File | Purpose |
|---|---|
| `inc/usersauth.functions.php` → `usersauth_loginAs()` | first check before login |
| `usersauth.header.first.php` (option B) | do not render the banner if rights were revoked |
| `usersauth.header.tags.php` (option A) | do not render the strip if rights were revoked |
| `usersauth.footer.tags.php` (option C) | do not render the toast if rights were revoked |
| `usersauth.users.details.tags.php` | do not render the button in the profile |
| `usersauth.users.loop.php` | do not render the button in the loop |
| `usersauth.tools.php` | second check after `cot_auth` |

### 6.4. `usersauth_loginAs($targetUserId, $countVisit = null)` — Full Login Cycle

**Possible results:**

| Result | When |
|---|---|
| `['error' => 'csrf']` | `usersauth_verifyCsrf()` returned `false` |
| `['error' => 'forbidden']` | `usersauth_isAllowed()` returned `false` |
| `['error' => 'not_found']` | `$targetUserId < 1` or the user was not found in the DB |
| `['error' => 'self']` | `$targetUserId === Cot::$usr['id']` and not yet impersonating |
| `['error' => 'superadmin']` | The target is a superadmin, but the current user is not |
| `['error' => 'auth_failed']` | `cot_user_authorize()` returned `error` |
| `['success' => true, 'user' => $user]` | Success |

**Step-by-step details:**

1. `usersauth_verifyCsrf()` — failed → `csrf`.
2. `usersauth_isAllowed()` — failed → `forbidden`.
3. `$targetUserId < 1` → `not_found`.
4. If `$countVisit === null` — reads from `Cot::$cfg['plugin']['usersauth']['count_visit']`. This allows the calling code to explicitly pass `true`/`false`, bypassing the config.
5. If `$targetUserId === (int)Cot::$usr['id']` **and** `$_SESSION['usersauth_original']` is empty → `self`. This protects against losing the "anchor" — when impersonation is already active, switching to oneself is allowed.
6. `SELECT * FROM cot_users WHERE user_id = ? LIMIT 1` — empty → `not_found`.
7. `user_id` and `user_maingrp` are cast to `int`.
8. `cot_fillGroupsForUser($user)` — fills `$user['groups']` (needed for the `superadmin` check and for `cot_user_authorize()`).
9. If the target is a superadmin, and `Cot::$usr['groups']` does not contain `COT_GROUP_SUPERADMINS` → `superadmin`.
10. If `$_SESSION['usersauth_original']` is empty — writes:
    ```
    ['id'         => Cot::$usr['id'],
     'name'       => Cot::$usr['name'],
     'maingrp'    => Cot::$usr['maingrp'],
     'token'      => Cot::$sys['xk'],
     'started_at' => time()]
    ```
    This is done **only once**, so that when switching from target-A to target-B, the anchor remains the original administrator.
11. `$_SESSION['usersauth_target'] = ['id' => ..., 'name' => ...]`.
12. Snapshot of `$prevLastlog`, `$prevLastvisit`, `$prevLogcount`, `$prevLastip`, `$prevToken` from `$user`.
13. `cot_user_authorize($user, true)` — full Cotonti authorization:
    - recalculates `sid` from `user_password + now`, updates `user_sid` / `user_sidtime` if needed;
    - updates `user_lastlog`, `user_lastvisit`, `user_logcount = user_logcount + 1`, `user_lastip`;
    - rotates `user_token` (`cot_unique(16)`);
    - sets the cookie/session `$sys['site_id']` to `base64(user_id:sha1(sid))`;
    - triggers hooks `users.authorize` and `users.authorize.done`.
14. If `$result['error']` is non-empty — `unset($_SESSION['usersauth_target'])`, return `auth_failed`. **Rollback of visit fields is not performed** — authorization did not happen, so there is nothing to restore.
15. If `!$countVisit` — restores `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip`, **and `user_token`**. Restoring `user_token` is important because `cot_user_authorize()` **always** rotates it; without rollback, CSRF tokens on the user's open tabs would break. `user_sid` does not need to be restored — `cot_user_authorize()` reuses it if it is valid.
16. Writes to `cot_log` with type `login`, sanitized `{ADMIN}`, `{USER}`, `{UID}`. Access to language strings — via `Cot::$L` (the global `$L` is unavailable inside the function without `global $L`, so the `Cot::$L` container is used).
17. Returns `['success' => true, 'user' => $user]`.

### 6.5. `usersauth_return()` — Return to the Original Account

**Possible results:**

| Result | When |
|---|---|
| `['error' => 'csrf']` | CSRF token mismatch |
| `['error' => 'no_original']` | `$_SESSION['usersauth_original']['id']` is empty |
| `['error' => 'not_found']` | The user with the saved ID was not found in the DB |
| `['error' => 'auth_failed']` | `cot_user_authorize()` returned `error` |
| `['success' => true]` | Success |

**Step-by-step details:**

1. `usersauth_verifyCsrf()` — failed → `csrf`.
2. If `$_SESSION['usersauth_original']['id']` is empty → `no_original`.
3. Loads `SELECT * FROM cot_users WHERE user_id = ? LIMIT 1`.
4. If not found — `usersauth_clearSession()` (full Cotonti logout), `unset` both plugin session variables, return `not_found`. Without a forced logout, the administrator would be "locked" under the target account with no way back.
5. `cot_user_authorize($user, true)` — authorization as the original.
6. `unset($_SESSION['usersauth_original'], $_SESSION['usersauth_target'])` — before checking the result, so as not to remain in a "half-state".
7. If `$result['error']` — `usersauth_clearSession()`, return `auth_failed`. The administrator does not get "stuck" under the target; the next request is processed as a guest — you can log in again.
8. Writes to `cot_log` with type `return`, sanitized `{USER}` (via `Cot::$L`).
9. Returns `['success' => true]`.

### 6.6. `usersauth_isExpired()` — Impersonation Time Control

```php
function usersauth_isExpired(): bool
```

**Logic:**

1. If `$_SESSION['usersauth_original']['started_at']` is empty → `false` (not in an impersonation state).
2. Reads `session_lifetime` from the config; `0` or negative → `false` (no limit).
3. Returns `(time() − started_at) > session_lifetime`.

**Where it is used:**

| File | Action on expiration |
|---|---|
| `usersauth.header.first.php` (option B) | calls `usersauth_return()`, redirects |
| `usersauth.header.tags.php` (option A) | calls `usersauth_return()`, redirects |
| `usersauth.tools.php` | calls `usersauth_return()`, shows `cot_message`, redirects to the admin panel |

In `usersauth.footer.tags.php` (option C), the `usersauth_isExpired()` check is **not performed** — at the time the footer is rendered, headers have already been sent, and `cot_redirect()` is not possible. If the administrator has selected only option C — auto-return will occur the next time the plugin's admin page is opened, or on the next request in which `header.first`/`header.user.tags` triggers (if they are also active).

### 6.7. `usersauth_safeRedirect($redirectB64)` — redirect Validation

```php
function usersauth_safeRedirect(string $redirectB64): string
```

**Logic:**

1. `$redirectB64 === ''` → `cot_url('index')`.
2. `base64_decode($redirectB64, true)` — strict mode; `false` or empty → `cot_url('index')`.
3. Must start with `/` (an internal absolute path); otherwise → `cot_url('index')`.
4. Rejects `//` and `/\` at the start — protocol-relative URLs.
5. Rejects any `\` in the string — attempts to deceive browsers on Windows.
6. Rejects `\r\n` — header injection.
7. Returns the decoded path.

**Application:** in `usersauth.php` before `cot_redirect($target)`.

**What is protected:** an attacker cannot, through the `redirect` parameter, lead the administrator after a successful login to an external domain (Open Redirect), to `javascript:` (XSS in the `Location` context), to a protocol-relative URL (`//evil.com`), or to a URL with injected headers.

### 6.8. `usersauth_sanitizeForLog($value)` — Sanitization Before Logging

```php
function usersauth_sanitizeForLog(string $value): string
{
    $value = preg_replace('/[\r\n\t\x00-\x1F]+/u', ' ', $value) ?? '';

    return mb_substr(trim($value), 0, 120, 'UTF-8');
}
```

**What it does:**

- strips `\r`, `\n`, `\t`, and all control characters (`0x00–0x1F`) — protection against log injection;
- `trim`;
- limits to 120 characters via `mb_substr` — protection against "bloating" records.

**Application:** in `usersauth_loginAs()` and `usersauth_return()` when composing text for `cot_log()`.

### 6.9. `usersauth_clearSession()` — Forced Logout

```php
function usersauth_clearSession(): void
{
    $siteId = (string) (Cot::$sys['site_id'] ?? '');
    if ($siteId === '') {
        return;
    }

    unset($_SESSION[$siteId]);
    cot_setcookie($siteId, '', time() - 3600);
}
```

**What it does:**

- removes the Cotonti session key (`$sys['site_id']`) from `$_SESSION`;
- resets the Cotonti cookie to the past (with `time() − 3600`) — the browser deletes it.

**Application:** in `usersauth_return()` on failed authorization of the original — so that the administrator does not get "stuck" under the target.

### 6.10. URL Helpers

#### `usersauth_url(int $userId, string $redirect = ''): string`

Login URL via the **admin panel**:

```
admin.php?m=other&p=usersauth&a=login&uid={UID}&x={TOKEN}[&redirect={BASE64}]
```

- `$redirect` is encoded in `base64_encode`.
- Always adds `Cot::$sys['xk']` (Cotonti XSS token).

#### `usersauth_publicUrl(int $userId): string`

Login URL from the **frontend**:

```
index.php?e=usersauth&a=login&uid={UID}&x={TOKEN}
```

Used in buttons on the profile page and in the user list.

#### `usersauth_returnUrl(): string`

Return URL:

```
index.php?e=usersauth&a=return&x={TOKEN}
```

### 6.11. `usersauth_isImpersonating(): bool`

Returns `true` if both `$_SESSION['usersauth_original']['id']` and `$_SESSION['usersauth_target']['id']` are non-empty at the same time.

### 6.12. Specifics of Working with Language Strings Inside Functions

`cot_rc($key, $params)` substitutes `{...}` placeholders **only if `$key` is a key from the `$R` array**. For ready-made strings from `$L`, substitution is **not performed** — `cot_rc()` will return the string as-is, with the literal `{NAME}`.

Inside functions, the global `$L` is also unavailable without `global $L`. Therefore:

- language strings are read via `Cot::$L[...]`;
- placeholders in strings from `$L` are substituted via `str_replace()`.

Example:

```php
cot_log(
    str_replace(
        ['{ADMIN}', '{USER}', '{UID}'],
        [$adminName, $userName, (string) $user['user_id']],
        Cot::$L['usersauth_log']
    ),
    'usersauth',
    'login',
    'done'
);
```

In template hooks (they are included in the global scope), `$L` works, but `{NAME}` substitution is still done via `str_replace()` because the source is `$L`, not `$R`.

---

## 7. Cotonti Hooks

The plugin registers **five mandatory hooks and three banner hooks, of which one is active**.

### Mandatory Hooks

| File | Hook | Purpose |
|---|---|---|
| `usersauth.tools.php` | `tools` | Main admin page (`admin.php?m=other&p=usersauth`) |
| `usersauth.php` | `standalone` | Public controller (`index.php?e=usersauth&a=login\|return&...`) |
| `usersauth.users.details.tags.php` | `users.details.tags` | "Log in as" button on the user details page |
| `usersauth.users.loop.php` | `users.loop` | "Log in as" button in the user list loop |

### Banner Hooks — Choose One

| File | Hook | Where the banner appears |
|---|---|---|
| `usersauth.header.tags.php` | `header.user.tags` | Strip in the site header (tags `{HEADER_USERSAUTH_*}`) |
| `usersauth.header.first.php` | `header.first` | Line inside the standard Cotonti notices block (`{HEADER_NOTICES}`) |
| `usersauth.footer.tags.php` | `footer.tags` | Floating Bootstrap 5 Toast in the lower-right corner |

Rename inactive banner files to `.bak` — otherwise they will also be registered and the banner will appear in several places at once.

### 7.1. `tools` — Admin Page

- **Trigger:** `admin.php?m=other&p=usersauth`.
- **What it does:**
  - `list($plugRead, $plugWrite, $plugAdmin) = cot_auth('plug', 'usersauth')` — **into local variables**, `Cot::$usr['isadmin']` is not overwritten;
  - `cot_block($plugAdmin)` — blocks if there is no admin right on the plugin;
  - `usersauth_isAllowed()` — second check;
  - checks `usersauth_isExpired()`, automatically returns on expiration;
  - processes `a=login&uid=X` (buttons in the admin table);
  - search via `LOCATE()`, limit 50;
  - renders `usersauth.admin.tpl`.

### 7.2. `standalone` — Public Controller

- **Trigger:** `index.php?e=usersauth&a=...`.
- **What it does:**
  - `a=login&uid=X` — `usersauth_loginAs()`, on success redirect via `usersauth_safeRedirect()`;
  - `a=return` — `usersauth_return()`, redirect to `index.php`;
  - `cot_check_xg()` — before executing all logic.

### 7.3. `users.details.tags` — Button in the Profile

- **Trigger:** rendering of the user details page.
- **Conditions:** `usersauth_isAllowed()`, `targetId >= 1`, `targetId !== Cot::$usr['id']`.
- **Assigns:**
  - `USERS_DETAILS_USERSAUTH_URL` — URL;
  - `USERS_DETAILS_USERSAUTH_LINK` — `<a class="btn btn-danger btn-sm usersauth-login-as" target="_blank" rel="noopener noreferrer">Log in as «{NAME}»</a>`;
  - `USERS_DETAILS_USERSAUTH_ICON` — icon `<i class="fa-solid fa-right-to-bracket"></i>` with tooltip.

### 7.4. `users.loop` — Button in the Loop

- **Trigger:** user loop (`users.tpl`).
- **Conditions:** same.
- **Assigns:**
  - `USERS_ROW_USERSAUTH_URL`;
  - `USERS_ROW_USERSAUTH_LINK` — compact button with `target="_blank" rel="noopener noreferrer"`;
  - `USERS_ROW_USERSAUTH_ICON` — icon button.

### 7.5. `header.user.tags` — Option A (Strip in the Header)

- **Trigger:** rendering of the site header.
- **What it does:**
  - `usersauth_isImpersonating()` → otherwise `return`;
  - `usersauth_isExpired()` → `usersauth_return()` + `cot_redirect()`;
  - assigns tags:
    - `HEADER_USERSAUTH_RETURN_URL` — return URL;
    - `HEADER_USERSAUTH_RETURN_LINK` — ready `<a class="btn btn-warning btn-sm">` with `rel="noopener noreferrer"`;
    - `HEADER_USERSAUTH_CURRENT` — text "Currently you are {NAME}" with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` and `str_replace('{NAME}', ...)`.
- **Condition for the hook to work:** the current user must have the `R` bit on `plug.usersauth` — otherwise `cot_getextplugins()` will not include this file. That is why `usersauth.setup.php` must specify `Auth_guests=R`, `Auth_members=R`.

### 7.6. `header.first` — Option B (Notices)

- **Trigger:** `system/header.php`, the very first hook.
- **What it does:**
  - `usersauth_isImpersonating()` → otherwise `return`;
  - `usersauth_isExpired()` → `usersauth_return()` + `cot_redirect()`;
  - adds a pair `[returnUrl, title]` to `Cot::$out['notices_array']`. The Cotonti core itself renders it in `{HEADER_NOTICES}` as `<li><a href="URL">TITLE</a></li>`.
- **Condition for the hook to work:** the theme's `header.tpl` must contain the `{HEADER_NOTICES}` or `{HEADER_NOTICES_ARRAY}` block.
- **Rights requirement:** as for any hook, the current user needs `R` on `plug.usersauth` (hence `Auth_guests=R`, `Auth_members=R`).

### 7.7. `footer.tags` — Option C (Toast)

- **Trigger:** `system/footer.php`, before assigning `FOOTER_RC`.
- **What it does:**
  - `usersauth_isImpersonating()` → otherwise `return`;
  - generates an HTML block `<div class="toast-container position-fixed bottom-0 end-0 p-3"><div class="toast show">…</div></div>` with a close button `data-bs-dismiss="toast"`;
  - appends this block to `Cot::$out['footer_rc']`.
- **Condition for the hook to work:** the theme's `footer.tpl` must contain the `{FOOTER_RC}` block.
- **Dependency:** Bootstrap 5 JS (bundle) to close the Toast via the close button.
- **Limitation:** `usersauth_isExpired()` is not called here — auto-return will occur the next time the plugin's admin page is opened, or on the next request where `header.first`/`header.user.tags` triggers.

---

## 8. Entry Points (URL Routes)

| URL | Handler | Purpose |
|---|---|---|
| `admin.php?m=other&p=usersauth` | `usersauth.tools.php` (hook `tools`) | Admin page with search |
| `admin.php?m=other&p=usersauth&a=login&uid={UID}&x={TOKEN}` | `usersauth.tools.php` | Login from the admin panel |
| `index.php?e=usersauth&a=login&uid={UID}&x={TOKEN}[&redirect={BASE64}]` | `usersauth.php` (hook `standalone`) | Public login |
| `index.php?e=usersauth&a=return&x={TOKEN}` | `usersauth.php` | Return to the original account |

The `x` parameter is `Cot::$sys['xk']`. Without it, `cot_check_xg()` will return an error.

The `redirect` parameter is base64 of an internal path. Only `/...` without `//`, `/\`, `\\`, and CRLF is accepted (see `usersauth_safeRedirect`). External URLs and absolute addresses are discarded, and the redirect goes to `index.php`.

---

## 9. Plugin API

All functions are declared in `inc/usersauth.functions.php` with `declare(strict_types=1)` and the check `defined('COT_CODE') or die('Wrong URL')`.

### 9.1. `usersauth_isAllowed(): bool`

Checks the current user's right to the tool. See section 6.3.

### 9.2. `usersauth_verifyCsrf(): bool`

Compares the `Cot::$sys['xk']` XSS token against the `x` parameter from GET. Timing-safe. See section 6.2.

### 9.3. `usersauth_loginAs(int $targetUserId, ?bool $countVisit = null): array`

Full authorization cycle. See section 6.4.

**Parameters:**

| Name | Type | Description |
|---|---|---|
| `$targetUserId` | `int` | Target user ID |
| `$countVisit` | `?bool` | `null` — from config; `true` — count; `false` — restore the snapshot |

**Returns:** `['error' => code]` or `['success' => true, 'user' => $user]`.

**Side effects:** writes `$_SESSION['usersauth_original']` (once) and `$_SESSION['usersauth_target']`, calls `cot_user_authorize()`, with `count_visit=0` restores `user_lastlog`/`user_lastvisit`/`user_logcount`/`user_lastip`/`user_token`, writes to `cot_log`.

### 9.4. `usersauth_return(): array`

Return to the original account. See section 6.5.

**Returns:** `['error' => code]` or `['success' => true]`.

**Side effects:** `cot_user_authorize()`, `unset` both session keys, on failure — `usersauth_clearSession()`, writes to `cot_log`.

### 9.5. `usersauth_url(int $userId, string $redirect = ''): string`

Login URL via the admin panel.

### 9.6. `usersauth_publicUrl(int $userId): string`

Login URL from the frontend.

### 9.7. `usersauth_returnUrl(): string`

Return URL.

### 9.8. `usersauth_isImpersonating(): bool`

`true` when impersonation is active.

### 9.9. `usersauth_isExpired(): bool`

`true` if `session_lifetime` is exceeded. See section 6.6.

### 9.10. `usersauth_safeRedirect(string $redirectB64): string`

Validates the base64 `redirect` and returns a safe URL. See section 6.7.

### 9.11. `usersauth_sanitizeForLog(string $value): string`

Strips control characters from a string and trims to 120 characters. See section 6.8.

### 9.12. `usersauth_clearSession(): void`

Full Cotonti logout: `unset` the session and reset the cookie to the past. See section 6.9.

### 9.13. Example of Use from Third-Party Code

```php
<?php
declare(strict_types=1);

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('usersauth', 'plug');

// Check whether the current user can use the tool
if (usersauth_isAllowed()) {
    // Generate a link for a button in your template
    $loginUrl = usersauth_publicUrl(152);
    $t->assign('MY_BTN_LOGIN_AS_URL', $loginUrl);
}

// Custom logic: log in as user 152 without counting the visit
$result = usersauth_loginAs(152, false);
if (!empty($result['error'])) {
    cot_error('Error: ' . $result['error']);
} else {
    cot_redirect(cot_url('index'));
}
```

---

## 10. Standard Cotonti Functions Used by the Plugin

### 10.1. Cotonti Functions

| Function | Where | Purpose |
|---|---|---|
| `cot_incfile('usersauth', 'plug')` | in all files | Include `inc/usersauth.functions.php` |
| `cot_langfile('usersauth', 'plug')` | wherever `$L[...]` is needed | Load the language file |
| `cot_tplfile('usersauth.admin', 'plug')` | `usersauth.tools.php` | Path to the template `tpl/usersauth.admin.tpl` |
| `cot_import($name, $source, $type[, $maxlen])` | everywhere | Import parameters with type casting |
| `cot_check_xg()` | `usersauth.php`, `usersauth.tools.php` | XSS token check; die on mismatch |
| `cot_auth('plug', 'usersauth')` | `usersauth.tools.php` | Check group rights on the plugin |
| `cot_block($flag)` | `usersauth.tools.php` | Block if there is no admin right |
| `cot_die_message($code, $direct = true)` | `usersauth.php`, `usersauth.tools.php` | Force a 403 response |
| `cot_error($msg)` | `usersauth.php`, `usersauth.tools.php` | Register an error |
| `cot_message($msg)` | `usersauth.php`, `usersauth.tools.php` | Register a message |
| `cot_redirect($url)` | `usersauth.php`, `usersauth.tools.php`, banner hooks | Redirect (terminates execution) |
| `cot_url($area, $params, $suffix, $secure)` | wherever a URL is needed | Build a URL |
| `cot_rc_link($url, $text, $attrs)` | template hooks and the admin template | Build an `<a>` |
| `cot_inputbox($type, $name, $value, $attrs)` | `usersauth.tools.php` | `<input>` field |
| `cot_build_group($id)` | `usersauth.tools.php` | Group name by ID |
| `cot_user_authorize($user, $remember)` | `usersauth_loginAs()`, `usersauth_return()` | Full authorization |
| `cot_fillGroupsForUser(&$user)` | `usersauth_loginAs()` | Fill `$user['groups']` |
| `cot_log($text, $type, $group, $status)` | `usersauth_loginAs()`, `usersauth_return()` | Write to `cot_log` |
| `cot_setcookie($name, $value, $expire, ...)` | `usersauth_clearSession()` | Reset the Cotonti cookie |

### 10.2. Global Objects and Constants

| Object | Where | What it is |
|---|---|---|
| `Cot::$db` | `inc/usersauth.functions.php`, `usersauth.tools.php` | Cotonti PDO. `Cot::$db->users` — the user table name |
| `Cot::$usr` | all files with checks | Current user (`id`, `name`, `groups`, `maingrp`, `auth_*`, `isadmin`) |
| `Cot::$cfg` | `usersauth_isAllowed()`, `usersauth_loginAs()`, `usersauth_isExpired()` | Configuration (`Cot::$cfg['plugin']['usersauth']`) |
| `Cot::$sys` | `usersauth_*Url()`, `usersauth_verifyCsrf()`, `usersauth_clearSession()` | System data (`xk`, `site_id`) |
| `Cot::$L` | `inc/usersauth.functions.php` | Language strings inside functions (the global `$L` is unavailable in functions) |
| `$L` | template hooks (global scope) | Language strings in template hooks |
| `Cot::$out['notices_array']` | `usersauth.header.first.php` (option B) | Array of `[url, title]` pairs — the core renders it in `{HEADER_NOTICES}` |
| `Cot::$out['footer_rc']` | `usersauth.footer.tags.php` (option C) | HTML included in the footer (`{FOOTER_RC}`) |
| `COT_GROUP_SUPERADMINS` | `usersauth_isAllowed()`, `usersauth_loginAs()` | Super administrator group ID (`5`) |
| `COT_GROUP_DEFAULT / GUESTS / INACTIVE / BANNED` | `usersauth_isAllowed()` | System groups (`0`, `1`, `2`, `3`) |
| `COT_CODE` | all files | Cotonti initialization flag |
| `COT_ADMIN` | `usersauth.tools.php` | "We are in the admin panel" flag |
| `XTemplate` | `usersauth.tools.php` | Cotonti template engine |

---

## 11. Session Variables

The plugin uses **two** session variables.

### 11.1. `$_SESSION['usersauth_original']`

Filled **once** — on the first switch in the current session. Represents the "anchor" — where we return to.

```php
$_SESSION['usersauth_original'] = [
    'id'         => (int) Cot::$usr['id'],       // ID of the original administrator
    'name'       => (string) Cot::$usr['name'],  // username
    'maingrp'    => (int) Cot::$usr['maingrp'],  // main group
    'token'      => (string) Cot::$sys['xk'],    // XSS token at the time of switching
    'started_at' => time(),                      // Unix time of impersonation start
];
```

The `started_at` key is used by `usersauth_isExpired()` to control `session_lifetime`. The `token` key is stored for possible future verification scenarios.

### 11.2. `$_SESSION['usersauth_target']`

Filled on every login. Contains information about who we are currently under.

```php
$_SESSION['usersauth_target'] = [
    'id'   => (int) $user['user_id'],
    'name' => (string) $user['user_name'],
];
```

On `usersauth_return()`, both variables are **removed**.

---

## 12. Security Model

The plugin uses **only standard Cotonti mechanisms** and adds its own layers of protection. Below — by layer.

### 12.1. Layer 1: CSRF Protection (Double)

- **Entry point:** `cot_check_xg()` in `usersauth.php` and `usersauth.tools.php` — checks the `x` token against `$sys['xk']` before executing any logic.
- **Functions:** `usersauth_verifyCsrf()` in `usersauth_loginAs()` and `usersauth_return()` — a timing-safe check via `hash_equals()`, triggered **before** any rights checks.

This protects against cases when a third-party plugin calls the function bypassing the entry point. Even if the CSRF token is missing or invalid, login/return will not be performed.

### 12.2. Layer 2: Authorization via `cot_user_authorize()`

The plugin **does not set** the cookie, `sid`, or XSS token itself. It calls the standard `cot_user_authorize($user, true)`, which does everything the same as with a regular login:

- recalculates `user_sid` / `user_sidtime` via HMAC-SHA256 with `Cot::$cfg['secret_key']`;
- updates `user_token` (CSRF);
- sets the Cotonti cookie with `HttpOnly` and optionally `Secure` (on HTTPS);
- triggers hooks `users.authorize` and `users.authorize.done`.

This guarantees compatibility with any plugins subscribed to login events.

### 12.3. Layer 3: Rights Checks Before Action

- `cot_auth('plug', 'usersauth')` in `usersauth.tools.php` — the `A` right on the plugin is mandatory.
- `cot_block($plugAdmin)` — blocks if there is no admin right.
- `usersauth_isAllowed()` — superadmin or a group from `allow_groups`.
- **`Cot::$usr['isadmin']` is not overwritten** — the global superadmin flag remains correct for all subsequent checks.

Result: to open the admin page, you must **simultaneously** pass `cot_auth`, `cot_block`, and `usersauth_isAllowed()`.

### 12.4. Layer 4: Prohibition of Dangerous Combinations

- **Impersonation of a superadmin is prohibited for a non-superadmin.** Returns `['error' => 'superadmin']`.
- **Impersonation of oneself is prohibited when the anchor is inactive.** Returns `['error' => 'self']`.
- **System groups are ignored in `allow_groups`** (`0`, `1`, `2`, `3`).

### 12.5. Layer 5: URL and Redirect Protection

- The `redirect` parameter is encoded in base64 and **strictly validated** via `usersauth_safeRedirect()`. Only internal paths are accepted.
- Rejected: absolute URLs, `//host`, `/\`, `\\`, CRLF.
- All plugin URLs contain the `x=$sys['xk']` parameter.

### 12.6. Layer 6: XSS Protection

- All usernames output into templates pass through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- In templates, `target="_blank"` is accompanied by `rel="noopener noreferrer"` — protection against tab-napping and referrer-leak (including leakage of the `x` token).

### 12.7. Layer 7: SQL Injection and LIKE-DoS Protection

- All SQL queries via prepared statements (`Cot::$db->query($sql, $params)`).
- Search via `LOCATE(:needle, column)`, not `LIKE` — the parameter is treated literally. `%` and `_` do not work as wildcards in the query, there is no LIKE injection, no DoS via `%...%`.
- `CAST(user_id AS CHAR) = :exact` — exact comparison by ID, without type casting on the SQL injection side.

### 12.8. Layer 8: Log Sanitization

`usersauth_sanitizeForLog()` strips `\r\n\t` and `0x00–0x1F`, limits to 120 characters. This protects against log injection — for example, attempts to "inject" HTML into a username and break the display of the **Admin → Tools → Logs** section, or to "bloat" a record with repeated newlines.

### 12.9. Layer 9: Restoring User State

With `count_visit=0`, after `cot_user_authorize()`, the following are restored:

- `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip` — statistics are not distorted;
- `user_token` — CSRF tokens on the user's open tabs are not broken.

### 12.10. Layer 10: Protection Against Getting Stuck in the Session

- `session_lifetime` — automatic return on expiration (in the admin panel and in the banner hooks of options A and B).
- `usersauth_clearSession()` — full logout if the original administrator cannot be restored (banned/deleted). The administrator does not get "stuck" under the target.
- `unset($_SESSION['usersauth_target'])` on a failed `cot_user_authorize()` in `usersauth_loginAs()` — no "half-state" remains.

### 12.11. Layer 11: Balanced Default Rights

`Auth_guests=R`, `Auth_members=R` — the minimum read bit required for `cot_getextplugins()` to include the banner hooks for the impersonated user (otherwise the administrator will not see the return button and will get "stuck" under someone else's account). The `A` right on `plug.usersauth` is not granted to anyone automatically — it is assigned only explicitly via **Admin → Rights → usersauth** by a superadmin.

`Lock_guests=W12345A` and `Lock_members=W12345A` block the remaining bits, but do not prevent assigning `A` via `admin.php?m=rights`.

### 12.12. What the Plugin Does NOT Protect

- **Does not block** viewing passwords — they are not stored in plaintext in Cotonti.
- **Does not protect** against the administrator themselves knowing their own password — outside the threat model.
- **Does not log failed attempts** — only successful login and successful return.
- **Does not limit** the number of switches between targets in one session — you can switch freely, the anchor is preserved.
- **Does not block** parallel work under different accounts in different browsers — this is outside the Cotonti session threat model.

---

## 13. Access Rights

Two independent check systems are used, and **both must pass**.

### 13.1. Standard Cotonti Rights

During installation, the plugin is registered in `cot_auth` with `auth_code='plug'` and `auth_option='usersauth'`. Masks from `usersauth.setup.php`:

```
Auth_guests=R
Lock_guests=W12345A
Auth_members=R
Lock_members=W12345A
```

Mask decoding:

- `0` — no rights;
- `R` (1) — read;
- `W` (2) — write;
- `1..5` (4/8/16/32/64) — custom bits;
- `A` (128) — administrator.

**Result:** during installation, guests and registered users receive only the `R` bit — this is necessary for `cot_getextplugins()` to include the banner hooks during their session. The `A` right on the plugin is not granted to anyone — super administrators (group 5) pass via standard Cotonti logic.

To grant access to a group:

**Admin → Rights → usersauth** — check the group, grant the `A` right.

### 13.2. `usersauth_isAllowed()` Check

In addition to Cotonti rights:

- guest — no;
- `COT_GROUP_SUPERADMINS` — yes;
- otherwise — the intersection of `allow_groups` (with system group filtering) and `Cot::$usr['groups']`.

### 13.3. Summary Table

| Scenario | `cot_auth` | `isAllowed` | Result |
|---|---|---|---|
| Superadmin | ✅ (by standard logic) | ✅ | Access |
| Group in `allow_groups` + right `A` | ✅ | ✅ | Access |
| Group in `allow_groups`, without right `A` | ❌ | — | 403 from `cot_auth` |
| Group with right `A`, without `allow_groups` | ✅ | ❌ | 403 from `isAllowed` |
| Guest | ❌ (only `R`) | — | 403 on login attempt |
| Inactive / banned | ❌ | — | 403 |

### 13.4. Internal Restrictions

Regardless of rights:

1. **You cannot log in as a superadmin** if the current user is not a superadmin.
2. **You cannot log in as yourself** when the anchor is inactive.
3. **The target user must exist** in `cot_users`.

---

## 14. Logging

Every successful login and return is written to `cot_log` via `cot_log($text, $type, $group, $status)`.

**Parameters:**

- `$type` — `'usersauth'`;
- `$group` — `'login'` (login) or `'return'` (return);
- `$status` — `'done'`.

**Texts:**

| Event | Key | Value (RU) |
|---|---|---|
| Login | `Cot::$L['usersauth_log']` | `Administrator {ADMIN} logged in as user {USER} (ID {UID})` |
| Return | `Cot::$L['usersauth_log_return']` | `Administrator returned from user mode {USER}` |

**Substitutions:**

- `{ADMIN}` — the name of the original administrator from `$_SESSION['usersauth_original']['name']`;
- `{USER}` — the target user's name;
- `{UID}` — the target user's ID.

All values pass through `usersauth_sanitizeForLog()` — log injection is impossible.

**Viewing:** **Admin → Tools → Logs** or `admin.php?m=other&p=log`.

---

## 15. Theme Template Integration

### 15.1. Return Banner — Three Options to Choose From. Integrate into Your Theme However You Prefer.

Only **one** banner file should be active at a time. Rename the other two to `.bak` — otherwise the banner will appear in several places.

| Option | File | What the user sees | What is needed in the template | Dependencies |
|---|---|---|---|---|
| **A. Header** | `usersauth.header.tags.php` | `alert-warning` strip in the header | Tags `{HEADER_USERSAUTH_*}` in `header.tpl` | Cotonti only |
| **B. Notices** | `usersauth.header.first.php` | Line inside the notices block | `{HEADER_NOTICES}` (or `{HEADER_NOTICES_ARRAY}`) in `header.tpl` | Cotonti only |
| **C. Toast** | `usersauth.footer.tags.php` | Floating Bootstrap Toast in the lower-right corner | `{FOOTER_RC}` in `footer.tpl` | Bootstrap 5 JS |

### 15.2. Option A — Strip in the Header (`header.user.tags`)

**Tags:**

| Tag | What it contains |
|---|---|
| `HEADER_USERSAUTH_RETURN_URL` | Return URL (`index.php?e=usersauth&a=return&x=...`) |
| `HEADER_USERSAUTH_RETURN_LINK` | Ready `<a class="btn btn-warning btn-sm" rel="noopener noreferrer">` with `title="Return to administrator account"` |
| `HEADER_USERSAUTH_CURRENT` | Text "Currently you are {NAME}" (name escaped) |

**Example of insertion into `header.tpl`:**

```html
<!-- IF {HEADER_USERSAUTH_RETURN_LINK} -->
<div class="container mt-2">
    <div class="alert alert-warning d-flex align-items-center justify-content-between">
        <span>{HEADER_USERSAUTH_CURRENT}</span>
        {HEADER_USERSAUTH_RETURN_LINK}
    </div>
</div>
<!-- ENDIF -->
```

The check `IF {HEADER_USERSAUTH_RETURN_LINK}` means: the strip is shown only when impersonation is active.

**Requirement:** the impersonated user must have the `R` bit on `plug.usersauth` — otherwise `cot_getextplugins()` will not include `usersauth.header.tags.php`, and the strip will not appear. This is precisely why `usersauth.setup.php` contains `Auth_guests=R`, `Auth_members=R`.

### 15.3. Option B — Line in Notices (`header.first`)

The file adds a pair `[returnUrl, 'Currently you are {NAME} — Return to your account']` to `Cot::$out['notices_array']`. The Cotonti core itself renders it in the `{HEADER_NOTICES}` (or `{HEADER_NOTICES_ARRAY}`) block as `<li><a href="URL">TITLE</a></li>`.

**What is needed in `header.tpl`:** the `{HEADER_NOTICES}` block — standard for modern Cotonti themes. If the block is missing, add:

```html
<!-- IF {HEADER_NOTICES} -->
<div class="container mt-2">
    <ul class="list-unstyled">
        {HEADER_NOTICES}
    </ul>
</div>
<!-- ENDIF -->
```

**What the user sees:** a standard notice link with the text "Currently you are Oleg — Return to your account".

**Advantages of this option:** no custom template edits beyond the existing notices block. No JS, no Bootstrap dependencies.

### 15.4. Option C — Bootstrap 5 Toast (`footer.tags`)

The file adds the following block to `Cot::$out['footer_rc']`:

```html
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="usersauth-return-toast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">User Auth Vision</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Currently you are: {NAME}
            <div class="mt-2">
                <a href="..." class="btn btn-warning btn-sm">Return to your account</a>
            </div>
        </div>
    </div>
</div>
```

**What is needed in `footer.tpl`:** the `{FOOTER_RC}` block — standard for Cotonti.

**Dependencies:** Bootstrap 5 CSS + JS (bundle, with Popper). If JS is not included, the close button will not work, but the banner will display and the return link will be clickable.

**Limitation:** `usersauth_isExpired()` is not checked here — auto-return will occur the next time the plugin's admin page is opened, or on the next request where `header.first`/`header.user.tags` triggers.

### 15.5. User Details Page (`users.details.tpl`)

| Tag | What it contains |
|---|---|
| `USERS_DETAILS_USERSAUTH_URL` | Login URL |
| `USERS_DETAILS_USERSAUTH_LINK` | `<a class="btn btn-danger btn-sm usersauth-login-as" target="_blank" rel="noopener noreferrer">Log in as «{NAME}»</a>` |
| `USERS_DETAILS_USERSAUTH_ICON` | Icon `<i class="fa-solid fa-right-to-bracket"></i>` with tooltip |

**Example of insertion into `users.details.tpl`:**

```html
<!-- IF {USERS_DETAILS_USERSAUTH_LINK} -->
<div class="usersauth-actions mt-3">
    {USERS_DETAILS_USERSAUTH_LINK}
    {USERS_DETAILS_USERSAUTH_ICON}
</div>
<!-- ENDIF -->
```

The button is rendered only if:

- the current user has the right to the plugin (`usersauth_isAllowed()`);
- the profile being viewed is **not their own**;
- `user_id >= 1`.

### 15.6. User List (`users.tpl`)

| Tag (in the `USERS_ROW` context) | What it contains |
|---|---|
| `USERS_ROW_USERSAUTH_URL` | Login URL |
| `USERS_ROW_USERSAUTH_LINK` | `<a class="btn btn-sm btn-outline-danger usersauth-login-as" target="_blank" rel="noopener noreferrer">Log in as «{NAME}»</a>` |
| `USERS_ROW_USERSAUTH_ICON` | Icon `<a class="btn btn-sm btn-danger"><i class="fa-solid fa-right-to-bracket"></i></a>` |

**Example of insertion into `users.tpl` (inside the `USERS_ROW` block):**

```html
<!-- BEGIN: USERS_ROW -->
<tr>
    <td>{USERS_ROW_ID}</td>
    <td>{USERS_ROW_NAME}</td>
    <!-- IF {USERS_ROW_USERSAUTH_LINK} -->
    <td class="text-center">{USERS_ROW_USERSAUTH_ICON}</td>
    <!-- ENDIF -->
</tr>
<!-- END: USERS_ROW -->
```

### 15.7. Default Admin Template (`tpl/usersauth.admin.tpl`)

Blocks:

| Block | Purpose |
|---|---|
| `MAIN` | All page content |
| `MAIN.IMPERSONATING` | Strip "Currently you are {NAME}" + return link |
| `MAIN.SEARCH_RESULTS` | Results table |
| `MAIN.SEARCH_RESULTS.ROW` | One row of the table |
| `MAIN.NO_RESULTS` | "No results" message if a query was made but there are no results |

**Tags in `MAIN`:**

| Tag | Value |
|---|---|
| `USERSAUTH_TITLE` | Title |
| `USERSAUTH_HELP` | Hint |
| `USERSAUTH_FORM_ACTION` | Form URL (`admin.php?m=other&p=usersauth`) |
| `USERSAUTH_SEARCH` | Search field |
| `USERSAUTH_SUBMIT` | Submit button |

**Tags in `MAIN.IMPERSONATING`:**

| Tag | Value |
|---|---|
| `USERSAUTH_CURRENT` | "Currently you are: {NAME}" (escaped) |
| `USERSAUTH_RETURN_LINK` | Return link |

**Tags in `MAIN.SEARCH_RESULTS.ROW`:**

| Tag | Value |
|---|---|
| `USERSAUTH_ROW_ID` | `user_id` |
| `USERSAUTH_ROW_NAME` | Username + `(First Last)` if filled in (escaped) |
| `USERSAUTH_ROW_EMAIL` | Email (escaped) |
| `USERSAUTH_ROW_GROUP` | Group name |
| `USERSAUTH_ROW_URL` | Login URL |
| `USERSAUTH_ROW_PROFILE` | Profile URL |

All links with `target="_blank"` in the template have `rel="noopener noreferrer"`.

---

## 16. User Guide

### 16.1. Logging in as a User from the Admin Panel

1. Open **Admin → Tools → User Auth Vision** or `admin.php?m=other&p=usersauth`.
2. Enter the username, e-mail, or ID in the search field.
3. Click **Search**.
4. In the results table, click **Log in as the selected user** next to the desired row.
5. The plugin authorizes you as this user and redirects to the home page (`index.php`).
6. Depending on the selected banner option, you will see:
   - a strip in the site header;
   - a line in the notices block;
   - a floating Toast in the lower-right corner.

### 16.2. Logging in as a User from the Profile

1. Open `index.php?m=users&a=details&id={UID}`.
2. If you have the right and this is **not your** profile — a "Log in as «{NAME}»" button will appear.
3. Click it — a new tab will open in which you are already under this user. The current tab remains under your account (session cookies become shared only after reload).
4. In the new tab, you will see the selected banner option.

### 16.3. Logging in as a User from the List

1. Open `index.php?m=users`.
2. In each row (except your own) a **Log in as «{NAME}»** button or icon will appear.
3. Click it — a new tab will open.

### 16.4. Returning to Your Account

**Manually:** click the **Return to your account** button in the banner (wherever it appears for you) or in the plugin's admin panel.

**Automatically:** after `session_lifetime` expires (default 3600 seconds), the plugin will return you on the next page render — in the admin panel or in the banner hooks of options A and B. For option C, auto-return will occur the next time the plugin's admin page is opened.

### 16.5. Configuring Visit Counting

1. **Admin → Extensions → usersauth → Settings**.
2. **"Count as a real visit"** — disabled by default (recommended).
3. Enable only if you are testing plugins that read `lastlog`/`lastvisit`/`logcount`/`lastip`.

### 16.6. Configuring Session Lifetime

1. **Admin → Extensions → usersauth → Settings**.
2. **"Impersonation session lifetime (sec.)"** — default `3600`.
3. `0` — no limit.

### 16.7. Allowing Non-Superadmins to Use It

1. **Admin → Extensions → usersauth → Settings** — in the **"Allowed groups"** field, specify group IDs separated by commas (e.g., `4,6`).
2. **Admin → Rights → usersauth** — check the same group, grant the `A` right.
3. Both conditions are mandatory.

### 16.8. What Happens During Navigation

- After login, the current tab is redirected to `index.php` or to the `redirect` parameter.
- Buttons in the profile and list open in a new tab (`target="_blank"`); the current tab remains under your account.
- The Cotonti session is shared across all tabs of one browser — switching in one tab will affect the others on the next request.
- For parallel work under two accounts, use different browsers or private mode.

---

## 17. File Structure

```
plugins/usersauth/
├── usersauth.setup.php                  # Plugin passport, registration, config
├── inc/
│   └── usersauth.functions.php          # Core: usersauth_* functions
├── usersauth.php                        # Standalone controller (standalone hook)
├── usersauth.tools.php                  # Admin page (tools hook)
├── usersauth.header.tags.php            # Banner option A (header.user.tags hook)
├── usersauth.header.first.php           # Banner option B (header.first hook)
├── usersauth.footer.tags.php            # Banner option C (footer.tags hook)
├── usersauth.users.details.tags.php     # Profile button (users.details.tags hook)
├── usersauth.users.loop.php             # Loop button (users.loop hook)
├── lang/
│   └── usersauth.ru.lang.php            # Russian localization
└── tpl/
    └── usersauth.admin.tpl              # Admin page template
```

| File | Responsibility |
|---|---|
| `usersauth.setup.php` | Plugin passport: code, version, rights, config, dependencies. **Not registered as a hook** — Cotonti skips files with the suffixes `setup`, `install`, `uninstall`, `configure` |
| `inc/usersauth.functions.php` | All business logic: rights checks, CSRF, login, return, URL helpers, sanitization, clearSession |
| `usersauth.php` | Public entry point: `a=login`, `a=return` |
| `usersauth.tools.php` | Admin page: search, table, form, impersonation strip |
| `usersauth.header.tags.php` | Banner option A: tags `HEADER_USERSAUTH_*` |
| `usersauth.header.first.php` | Banner option B: line in `Cot::$out['notices_array']` |
| `usersauth.footer.tags.php` | Banner option C: Bootstrap Toast in `Cot::$out['footer_rc']` |
| `usersauth.users.details.tags.php` | Tags `USERS_DETAILS_USERSAUTH_*` |
| `usersauth.users.loop.php` | Tags `USERS_ROW_USERSAUTH_*` |
| `lang/usersauth.ru.lang.php` | All UI texts, setting names, messages |
| `tpl/usersauth.admin.tpl` | Admin page markup |

**One** of the three banner files must be active. Rename the others to `.bak` — otherwise Cotonti will register all three hooks and the banner will appear in several places at once.

---

## 18. Troubleshooting

### 18.1. "Log in as" Buttons Do Not Appear in the Profile or List

**Causes:**

1. The current user is a guest or does not pass `usersauth_isAllowed()`. Check that you are a superadmin or that your group is added to `allow_groups`.
2. You are viewing **your own** profile — the button is not rendered for your profile (protection against losing the anchor).
3. The theme template does not contain the corresponding tags. Add `{USERS_DETAILS_USERSAUTH_LINK}` and `{USERS_ROW_USERSAUTH_LINK}` to the templates (see section 15).

### 18.2. Return Banner Does Not Appear After Logging in as a User

Check according to the banner option.

**Option A — strip in the header:**

1. `header.tpl` contains the block `IF {HEADER_USERSAUTH_RETURN_LINK}` (section 15.2).
2. In `usersauth.setup.php`, the parameters `Auth_guests=R` and `Auth_members=R` — without the `R` bit on `plug.usersauth`, `cot_getextplugins()` does not include `usersauth.header.tags.php` for the impersonated user.
3. The file `usersauth.header.tags.php` is active (not renamed to `.bak`).

**Option B — notices:**

1. `header.tpl` contains the `{HEADER_NOTICES}` or `{HEADER_NOTICES_ARRAY}` block.
2. The file `usersauth.header.first.php` is active.
3. `usersauth.setup.php` contains `Auth_guests=R`, `Auth_members=R`.

**Option C — Toast:**

1. `footer.tpl` contains the `{FOOTER_RC}` block.
2. The file `usersauth.footer.tags.php` is active.
3. Bootstrap 5 CSS+JS is included in the theme.
4. `usersauth.setup.php` contains `Auth_guests=R`, `Auth_members=R`.

**Common cause for all three options:** impersonation is not active. The plugin only fills the banner when `usersauth_isImpersonating() = true`.

### 18.3. The Banner Shows the Literal `{NAME}` Instead of the Username

`cot_rc($string, $params)` substitutes `{...}` placeholders **only for `$R` keys**, not for `$L`. In the banner files and in the logs, the plugin uses `str_replace('{NAME}', ...)` directly. If you edited the plugin code and restored `cot_rc(...)` for a string from `$L` — change it back to `str_replace`.

Verification:

```php
var_dump(cot_rc('Log in as «{NAME}»', ['NAME' => 'Ivan']));
// → string(31) "Log in as «{NAME}»"  ← placeholder NOT substituted

var_dump(str_replace('{NAME}', 'Ivan', 'Log in as «{NAME}»'));
// → string(23) "Log in as «Ivan»"
```

### 18.4. Error "Undefined variable $L" or "Trying to access array offset on null" in `usersauth.functions.php`

Inside functions, the global `$L` is not visible. Access to language strings inside `usersauth_loginAs()` and `usersauth_return()` must go through `Cot::$L[...]`, not `$L[...]`.

In template hooks (`usersauth.header.first.php`, `usersauth.header.tags.php`, `usersauth.footer.tags.php`, `usersauth.users.details.tags.php`, `usersauth.users.loop.php`), `$L` works because these files are included in the global scope via `system/plugin.php` and `system/header.php`/`system/footer.php`.

### 18.5. Error "You do not have permission to use this tool"

`usersauth_isAllowed()` was triggered. Solution: add your group to `allow_groups`.

### 18.6. 403 Error When Opening the Plugin Page in the Admin Panel

`cot_auth('plug', 'usersauth')` or `cot_block($plugAdmin)` was triggered. Solution: in **Admin → Rights → usersauth**, grant the group the `A` right on the plugin.

### 18.7. Error "Logging in as another super administrator is prohibited"

You are not a super administrator and are trying to log in as a super administrator. This is protection against privilege escalation. Log in as a superadmin yourself — or ask the superadmin to perform the action.

### 18.8. Error "You cannot log in as yourself"

You clicked the button for your own ID while in a normal state. Open someone else's profile. If you are already impersonating, this error does not occur.

### 18.9. Error "Security error (CSRF)"

The CSRF token does not match or is missing in the URL. Check that the link contains the parameter `x=$sys['xk']`. Refresh the page and try again. If you are calling `usersauth_loginAs()` from your own code — do not forget to pass `x` in the URL, or call `cot_check_xg()` in your entry point.

### 18.10. Error "Failed to authorize"

`cot_user_authorize()` returned an error. Possible causes:

- the target user is banned (`COT_GROUP_BANNED`);
- the target user is inactive (`COT_GROUP_INACTIVE`) and `inactive_login` is disabled in the Cotonti config;
- the original administrator has been deleted/banned — then the session will be forcibly closed, and you need to log in again.

### 18.11. Impersonation Session Expired

After `session_lifetime` expires, you are automatically returned to your account (in the admin panel and in the banner hooks of options A and B). If you want it to last longer — increase `session_lifetime` in the settings (or set `0` to disable the limit). For option C, auto-return will occur the next time the plugin's admin page is opened.

### 18.12. Unable to Return to the Administrator Account

**Causes:**

1. The original administrator has been deleted/banned — the plugin forcibly closed your session; you need to log in again.
2. The session has expired (you have not logged in for a long time), cookies were cleared, or you changed browsers.
3. The anchor (`$_SESSION['usersauth_original']`) was manually destroyed.

**Solution:** log in to the admin panel in the usual way — you will automatically be under your account.

### 18.13. The Admin Search Returns No Results

`LOCATE()` is used — substring search, **without wildcard characters**. Enter a substring of the username/email or an exact ID. The `%` character will be searched literally.

### 18.14. The Session Does Not Switch Between Tabs

This is standard Cotonti behavior. The session is shared across all tabs of one browser. Plugin buttons have `target="_blank"` — they open a new tab, but the session is shared.

**Solution:** for parallel work under two accounts — different browsers or private mode.

### 18.15. The Banner Appeared in Several Places at Once

Several banner files are active. Leave one, rename the others to `.bak`:

- `usersauth.header.tags.php` — option A;
- `usersauth.header.first.php` — option B;
- `usersauth.footer.tags.php` — option C.

After renaming — **Admin → Extensions → usersauth → Update** and clear the cache.

---

## 19. Glossary

| Term | Meaning |
|---|---|
| **Impersonation** | The process of logging in under another account. The plugin performs full authorization on behalf of the target user. |
| **Anchor** | The `$_SESSION['usersauth_original']` record — the original administrator account to which we return via `usersauth_return()`. |
| **Target user** | The user under whom the login is performed (`$targetUserId` / `$uid`). |
| **`cot_user_authorize()`** | Standard Cotonti function: full authorization (cookie, sid, XSS, `user_token`, `lastlog`). |
| **`cot_fillGroupsForUser()`** | Standard Cotonti function: fills `$user['groups']` (array of group IDs). |
| **XSS token (`Cot::$sys['xk']`)** | CSRF protection token. Passed in the URL as `x`. Checked by `cot_check_xg()` and `usersauth_verifyCsrf()`. |
| **`cot_log()`** | Standard function for writing to `cot_log`. The plugin writes with type `usersauth`, group `login` or `return`. |
| **`hash_equals()`** | PHP function for timing-safe string comparison. Used in `usersauth_verifyCsrf()` for protection against timing attacks. |
| **`session_lifetime`** | Plugin setting: impersonation session lifetime in seconds. |
| **`count_visit`** | Setting: count the visit or restore `lastlog`/`lastvisit`/`logcount`/`lastip`/`user_token`. |
| **`allow_groups`** | Setting: list of group IDs allowed access. System groups `0/1/2/3` are ignored. |
| **`usersauth_safeRedirect()`** | Base64-`redirect` validation function: only internal paths are allowed. |
| **`usersauth_sanitizeForLog()`** | Strips control characters from a string, trims to 120. |
| **`usersauth_clearSession()`** | Full Cotonti logout on failed return. |
| **`usersauth_isExpired()`** | Check for exceeding `session_lifetime`. |
| **`usersauth_verifyCsrf()`** | Internal timing-safe check of the `x` token. |
| **`header.user.tags`** | Cotonti hook: rendering of the user block in the theme header. Used by option A. |
| **`header.first`** | Cotonti hook: the very first hook of `system/header.php`. Used by option B. |
| **`footer.tags`** | Cotonti hook: before assigning `{FOOTER_RC}` in `system/footer.php`. Used by option C. |
| **`users.details.tags`** | Cotonti hook: rendering of the user details page. |
| **`users.loop`** | Cotonti hook: loop over users (list). |
| **`tools`** | Cotonti hook: tools page in the admin panel. |
| **`standalone`** | Cotonti hook: public controllers (`index.php?e=code`). |
| **`{HEADER_NOTICES}`** | Standard Cotonti notices block in `header.tpl`. Used by option B. |
| **`{FOOTER_RC}`** | Standard resources block in `footer.tpl`. Used by option C. |
| **Bootstrap Toast** | Bootstrap 5 component for pop-up notifications. Requires `bootstrap.bundle.min.js`. |
| **`LOCATE()`** | SQL substring search function without interpreting `%`/`_` — used in search for protection against LIKE injection. |
| **`rel="noopener noreferrer"`** | Attribute of `<a target="_blank">` preventing tab-napping and Referer leakage. |
| **`Cot::$usr['isadmin']`** | Global super administrator flag in Cotonti. The plugin **does not overwrite** it — it uses local variables. |
| **`Cot::$cfg['plugin']['usersauth']`** | Plugin settings array from `cot_config`. |
| **`Cot::$L`** | Language strings available from any scope. Used inside the plugin functions. |
| **`COT_GROUP_SUPERADMINS`** | Constant of the super administrator group ID (`5`). |
| **`COT_GROUP_DEFAULT / GUESTS / INACTIVE / BANNED`** | System groups (`0`, `1`, `2`, `3`), discarded in `allow_groups`. |
| **Right `A`** | Administrator bit in Cotonti masks (value `128`). |
| **Right `R`** | Read bit in Cotonti masks (value `1`). |
| **BC (backward compatibility)** | Backward compatibility — not applicable; all plugin functions are its own. |

---

## 20. License and Links

**License:** BSD. See the plugin file headers and the `LICENSE` file in the repository.

```
Copyright (c) 2026 webitproff
https://github.com/webitproff/usersauth-cotonti
```

**Author:** [webitproff](https://github.com/webitproff)

**Links:**

- 🏠 Repository: https://github.com/webitproff/usersauth-cotonti
- 🛒 Marketplace: https://abuyfile.com/market/cotonti/plugs/usersauth
- 💬 Support: https://abuyfile.com/forums/cotonti/custom/
- 🌐 Cotonti CMF: https://github.com/Cotonti/Cotonti
- 📖 Cotonti Docs: https://www.cotonti.com/

___

---  
> RU  
---  


# User Auth Vision для Cotonti

Плагин для Cotonti CMF, позволяющий суперадминистратору (и, опционально, пользователям разрешённых групп) безопасно авторизоваться под любой учётной записью пользователя для тестирования, проверки прав, отладки шаблонов и сценариев работы сайта — с возможностью одним действием вернуться в свой аккаунт.

[![Version](https://img.shields.io/badge/version-2.2.1-green.svg)](https://github.com/webitproff/usersauth-cotonti)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti-v.1-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple.svg)](https://www.php.net/releases/8_5_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/usersauth-cotonti/blob/main/LICENSE)



- **Репозиторий:** https://github.com/webitproff/usersauth-cotonti
- **Маркетплейс:** https://abuyfile.com/market/cotonti/plugs/usersauth
- **Поддержка:** https://abuyfile.com/forums/cotonti/custom/
- **Cotonti CMF:** https://github.com/Cotonti/Cotonti

**Версия:** 2.2.1  
**Дата:** 2026-11-19  
**Автор:** [webitproff](https://github.com/webitproff)  
**Лицензия:** BSD

---

## Оглавление

1. [Назначение](#1-назначение)
2. [Ключевые возможности](#2-ключевые-возможности)
3. [Требования](#3-требования)
4. [Установка](#4-установка)
5. [Настройки плагина](#5-настройки-плагина)
6. [Бизнес-логика и алгоритмы](#6-бизнес-логика-и-алгоритмы)
7. [Хуки Cotonti](#7-хуки-cotonti)
8. [Точки входа (URL-маршруты)](#8-точки-входа-url-маршруты)
9. [API плагина](#9-api-плагина)
10. [Штатные функции Cotonti, используемые плагином](#10-штатные-функции-cotonti-используемые-плагином)
11. [Сессионные переменные](#11-сессионные-переменные)
12. [Модель безопасности](#12-модель-безопасности)
13. [Права доступа](#13-права-доступа)
14. [Логирование](#14-логирование)
15. [Интеграция в шаблоны темы](#15-интеграция-в-шаблоны-темы)
16. [Руководство пользователя](#16-руководство-пользователя)
17. [Структура файлов](#17-структура-файлов)
18. [Решение проблем](#18-решение-проблем)
19. [Глоссарий](#19-глоссарий)
20. [Лицензия и ссылки](#20-лицензия-и-ссылки)

---

## 1. Назначение

При разработке и сопровождении сайта на Cotonti часто требуется увидеть сайт глазами другого пользователя — обычного участника, автора в конкретной группе, модератора, — чтобы проверить работу прав, шаблонов, доступность разделов и интерактивных элементов. Стандартные средства Cotonti этого не позволяют: смена пароля, ручной вход под чужой учёткой или создание тестового юзера с теми же группами — неудобно и рискованно.

Плагин **User Auth Vision** решает эту задачу:

- Предоставляет администратору единый интерфейс для поиска нужного пользователя.
- Выполняет **полноценную авторизацию** от его имени — идентичную обычному входу (cookie, `sid`, `user_token`, XSS-токен).
- Даёт **мгновенный возврат** в исходный аккаунт администратора по одной ссылке, без ввода пароля.
- Позволяет **не засчитывать** визит в статистику целевого пользователя — чтобы не искажать его метрики.
- Пишет все действия в лог Cotonti.
- Не хранит никаких «обходных» механизмов — использует штатный `cot_user_authorize()`, поэтому все права, группы, хуки, плагины и темы работают так же, как при реальном входе.

Плагин **не имеет собственных таблиц** в БД и работает только со штатными `cot_users`, `cot_auth`, `cot_core`, `cot_config`, `cot_log`.

---

## 2. Ключевые возможности

- ✅ Вход под любым пользователем из админ-панели: поиск по логину, e-mail или ID.
- ✅ Вход под пользователем из его профиля (страница `users.details`).
- ✅ Вход под пользователем из общего списка пользователей (цикл `users.loop`).
- ✅ **Три независимых варианта отображения баннера возврата** — администратор включает тот, что подходит его теме:
  - **A. Шапка сайта** через хук `header.user.tags` — плашка в стиле `alert-warning` с тегами `{HEADER_USERSAUTH_*}`.
  - **B. Блок notices Cotonti** через хук `header.first` — строка внутри `{HEADER_NOTICES}`, без правок шаблона.
  - **C. Bootstrap 5 Toast** через хук `footer.tags` — плашка в правом нижнем углу, не зависит от шапки.
- ✅ Возврат в исходный аккаунт администратора по одной ссылке.
- ✅ Настройка списка групп, которым разрешён инструмент (по умолчанию — только суперадминистраторы).
- ✅ Запрет на вход под суперадминистратором для не-суперадминистраторов.
- ✅ Запрет входа под самим собой.
- ✅ Опция «не засчитывать визит»: восстанавливает `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip` и `user_token` целевого пользователя к предыдущим значениям.
- ✅ **Лимит времени имперсонации** (`session_lifetime`): по истечении сессия автоматически возвращается в исходный аккаунт.
- ✅ **Принудительный logout** при неудачном возврате — администратор не «застревает» под чужой учёткой, если исходный аккаунт забанен/удалён.
- ✅ **Защита от CSRF** внутри самих функций `loginAs()` / `return()` — даже при вызове из стороннего кода.
- ✅ **Защита от Open Redirect** через `usersauth_safeRedirect()` — разрешены только внутренние пути.
- ✅ **Защита от XSS** через `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` для всех имён, выводимых в шаблоны.
- ✅ **Защита от LIKE-инъекции и DoS** в поиске через `LOCATE()`.
- ✅ **Защита от tab-napping и referrer-leak** через `rel="noopener noreferrer"` на всех `target="_blank"`.
- ✅ **Санитизация логов** через `usersauth_sanitizeForLog()`.
- ✅ **Права по умолчанию сбалансированы**: `Auth_guests=R`, `Auth_members=R` — минимум, необходимый для того, чтобы хук баннера срабатывал у имперсонированного пользователя. Право `A` на плагин никому не выдаётся автоматически — только явно через **Админ → Права**.
- ✅ **Строгая типизация** (`declare(strict_types=1)`) во всех файлах.
- ✅ **Фильтрация системных групп** (0/1/2/3) в `allow_groups`.
- ✅ Совместимость с Bootstrap 5 и Font Awesome.
- ✅ Не требует модификации ядра Cotonti.

---

## 3. Требования

| Компонент | Требование |
|---|---|
| CMS | Cotonti CMF v1.x |
| PHP | **8.5** и выше (`declare(strict_types=1)`, `str_starts_with`, `str_contains`, typed signatures, `random_bytes`) |
| MySQL | **8.4** и выше (используются `CAST`, `LIMIT`, `LOCATE`, prepared statements) |
| Модуль `users` | Обязателен (`Requires_modules=users` в setup-файле) |
| Расширение `mbstring` | Обязательно (штатное требование Cotonti) |
| Font Awesome (опционально) | Для иконки `fa-solid fa-right-to-bracket` в кнопках |
| Bootstrap 5 (опционально) | Для варианта C (Toast), а также для стилизации кнопок (классы `btn`, `alert`, `form-control`) |

Плагин **не создаёт собственных таблиц**. Использует штатные:

- `cot_users` — целевые учётные записи, восстановление visit-полей и `user_token`;
- `cot_auth` — права группы на плагин (`auth_code='plug'`, `auth_option='usersauth'`);
- `cot_core` — регистрация плагина (`ct_code='usersauth'`);
- `cot_config` — настройки (`config_cat='usersauth'`);
- `cot_log` — записи о логине и возврате.

---

## 4. Установка

### 4.1. Установка через Cotonti

1. Скачайте архив плагина из репозитория или маркетплейса.
2. Загрузка на сервер (FTP/SSH). Распакуйте архив. Внутри будет папка с именем плагина (в нашем случае usersauth). Эту папку нужно скопировать в каталог /plugins/ на сервере — через FTP-клиент, SSH или файловый менеджер хостинга. Путь должен получиться таким: plugins/usersauth/ (внутри — usersauth.setup.php и остальные файлы).
3. Установка через админ-панель. После загрузки файлов зайдите в админку Cotonti → раздел Расширения (admin.php?m=extensions). В списке расширений появится строка usersauth. Нажмите Установить. Плагин не создает своих таблиц, но лучше сделать бекап БД.
4. Настройка. После установки перейдите в Расширения → usersauth → Настройки и выставьте параметры.
5. Права. уже увсе настроено при установке - ничего не трогать.

Затем в админ-панели: **Расширения → usersauth → Установить**.

### 4.3. Что происходит при установке

1. Читается `usersauth.setup.php` (блок `BEGIN_COT_EXT`).
2. Проверяется зависимость `Requires_modules=users`.
3. Сканируется каталог `plugins/usersauth/`; каждый файл `usersauth.*.php`, кроме `setup`, `install`, `uninstall`, `configure`, читается как хук-файл, из блока `BEGIN_COT_EXT` берётся поле `Hooks`.
4. В `cot_core` записывается строка `ct_code='usersauth'`, `ct_version='2.2.1'`, `ct_plug=1`.
5. В `cot_plugins` записываются все хуки.
6. В `cot_auth` записываются права для групп согласно `Auth_guests`, `Lock_guests`, `Auth_members`, `Lock_members`.
7. В `cot_config` записываются дефолтные значения настроек: `count_visit='0'`, `allow_groups=''`, `session_lifetime='3600'`.

SQL-скриптов и PHP-обработчиков установки нет — каталог `setup/` отсутствует.

### 4.4. Удаление

Через **Расширения → usersauth → Удалить**. Cotonti:

- удалит записи `cot_plugins` с `pl_code='usersauth'`;
- удалит записи `cot_auth` с `auth_code='plug'` и `auth_option='usersauth'`;
- удалит записи `cot_config` с `config_cat='usersauth'`;
- удалит запись `cot_core` с `ct_code='usersauth'`.

Файлы плагина автоматически **не удаляются** — их нужно стереть из `plugins/usersauth/` вручную. Таблицы `cot_users`, `cot_log` не затрагиваются.

---

## 5. Настройки плагина

Хранятся в `Cot::$cfg['plugin']['usersauth']`. Доступны в:

- **Админ → Расширения → usersauth → Настройки**
- прямо: `admin.php?m=config&n=edit&o=plug&p=usersauth`

| # | Ключ | Тип | По умолчанию | Значения |
|---|---|---|---|---|
| 01 | `count_visit` | radio | `0` | `0` / `1` |
| 03 | `allow_groups` | text | (пусто) | ID групп через запятую |
| 04 | `session_lifetime` | string | `3600` | целое ≥ 0 (секунды) |

### 5.1. `count_visit` — Засчитывать как реальный визит

- **Что делает:** если включено (`1`), при входе под пользователем его поля `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip` и `user_token` обновятся так, как если бы пользователь зашёл сам.
- **Если выключено (`0`, дефолт):** плагин снимает текущие значения этих полей **до** `cot_user_authorize()`, а после авторизации **восстанавливает** их. Визит администратора не попадает в статистику пользователя, не сбивает счётчик, не «палит» последний IP админа и не ротирует CSRF-токен открытых вкладок пользователя.
- **Зачем:** для тестирования это правильное поведение — вы не должны искажать статистику пользователя. Включайте `count_visit=1` только если тестируете плагины, которые читают `user_lastlog` / `user_lastvisit` / `user_logcount` / `user_lastip`, или хотите «засчитать» реальный визит.

### 5.2. `allow_groups` — Разрешённые группы

- **Что делает:** список ID групп Cotonti, пользователи которых могут пользоваться плагином, помимо суперадминистраторов (`COT_GROUP_SUPERADMINS`, обычно `5`).
- **Формат:** строка через запятую, например `4,6`.
- **Пусто (`''`):** плагин доступен только суперадминистраторам.
- **Системные группы игнорируются:** значения `0` (Default), `1` (Guests), `2` (Inactive), `3` (Banned) отбрасываются даже если указаны. Это защита от случайной выдачи инструмента гостям/забаненным.
- **Дополнительно** группа должна получить право `A` на `plug.usersauth` в **Админ → Права → usersauth** (см. раздел 13). Без этого доступ к админ-странице будет закрыт ещё до выполнения `usersauth_isAllowed()`.

### 5.3. `session_lifetime` — Время жизни сессии имперсонации

- **Что делает:** задаёт, через сколько секунд после начала имперсонации администратор будет автоматически возвращён в свой аккаунт.
- **По умолчанию:** `3600` (1 час).
- **`0`:** без ограничений по времени (сессия живёт до ручного возврата или до истечения cookie Cotonti).
- **Как работает проверка:**
  - при первом логине в `$_SESSION['usersauth_original']['started_at']` записывается текущий `time()`;
  - `usersauth_isExpired()` вызывается в админ-странице (`usersauth.tools.php`) и в выбранном баннерном хуке — `header.first` или `header.user.tags`. Для варианта C (`footer.tags`) проверка не выполняется: на момент рендера футера заголовки уже отправлены, `cot_redirect()` невозможен;
  - если `time() − started_at > session_lifetime` и `session_lifetime > 0` — плагин автоматически выполняет `usersauth_return()` и редиректит в исходный аккаунт;
  - в админке при истечении выводится сообщение `usersauth_error_session_expired`.

Пример: `session_lifetime=1800` — через 30 минут администратора автоматически вернёт к нему самому.

---

## 6. Бизнес-логика и алгоритмы

### 6.1. Общая схема потока

```
┌──────────────────────────────────────────────────────────────┐
│ Вход в админку или клик по кнопке на фронтенде                │
└──────────────────────────────────┬───────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────┐
│ usersauth_verifyCsrf() — проверка XSS-токена в функции       │
│   └─ невалидный → ['error' => 'csrf']                        │
└──────────────────────────────────┬───────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────┐
│ usersauth_isAllowed()                                        │
│   ├─ гость → false                                           │
│   ├─ superadmin → true                                       │
│   └─ allow_groups (без системных) ∩ user.groups → t/f        │
└──────────────────────────────────┬───────────────────────────┘
                                   │ true
                                   ▼
┌──────────────────────────────────────────────────────────────┐
│ usersauth_loginAs($uid, $countVisit=null)                    │
│   1. проверка target_id, self, существования юзера           │
│   2. cot_fillGroupsForUser($user)                            │
│   3. запрет non-superadmin → superadmin                      │
│   4. сохранить original + started_at в $_SESSION             │
│   5. сохранить target в $_SESSION                            │
│   6. снимок visit-полей + user_token                         │
│   7. cot_user_authorize($user, true)                         │
│   8. при count_visit=0 — восстановить снимок                 │
│   9. cot_log() с санитизацией                                │
└──────────────────────────────────┬───────────────────────────┘
                                   │
                                   ▼
                Пользователь авторизован под target
              Баннер возврата появляется в выбранном месте
                 (шапка / notices / Bootstrap Toast)

┌──────────────────────────────────────────────────────────────┐
│ Автовозврат: usersauth_isExpired() → usersauth_return()      │
│   запускается в пользовательском entry-point или в выбранном │
│   баннерном хуке                                             │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│ Ручной возврат: usersauth_return()                           │
│   1. usersauth_verifyCsrf()                                  │
│   2. $_SESSION['usersauth_original']['id'] существует?       │
│   3. загрузить original из БД                                │
│      └─ не найден → clearSession + unset → not_found         │
│   4. cot_user_authorize($original, true)                     │
│   5. unset сессионных ключей                                 │
│   6. при ошибке авторизации → clearSession → auth_failed     │
│   7. cot_log() с санитизацией                                │
└──────────────────────────────────────────────────────────────┘
```

### 6.2. `usersauth_verifyCsrf()` — внутренняя CSRF-проверка

```php
function usersauth_verifyCsrf(): bool
{
    $token    = (string) cot_import('x', 'G', 'ALP');
    $expected = (string) (Cot::$sys['xk'] ?? '');

    return $expected !== '' && hash_equals($expected, $token);
}
```

**Логика:**

1. Импортирует параметр `x` из GET (`cot_import('x', 'G', 'ALP')`).
2. Сравнивает с `Cot::$sys['xk']` через `hash_equals()` — timing-safe сравнение, не подверженное атакам по времени.
3. Возвращает `false`, если ожидаемый токен пуст.

**Где вызывается:** внутри `usersauth_loginAs()` и `usersauth_return()` — **первой строкой**, до любых проверок прав. Это defence-in-depth для случаев, когда функции вызывают из стороннего кода в обход entry-point'ов (`usersauth.php` / `usersauth.tools.php`, где `cot_check_xg()` тоже стоит).

### 6.3. `usersauth_isAllowed()` — проверка права на использование

```php
function usersauth_isAllowed(): bool
```

**Пошаговая логика:**

1. `Cot::$usr['id']` приводится к `int`; если `< 1` → `false` (гость).
2. `Cot::$usr['groups']` приводится к массиву `int`; пустые и нечисловые отбрасываются.
3. Если массив пуст → `false`.
4. Если `COT_GROUP_SUPERADMINS` в массиве → `true` (суперадмин проходит всегда).
5. Читает `Cot::$cfg['plugin']['usersauth']['allow_groups']`; пусто → `false`.
6. Разбивает по `,`, приводит к `int`, фильтрует нулевые значения.
7. **Отсекает системные группы** `0`, `1`, `2`, `3` — это защита от неверной настройки.
8. Если после фильтрации массив пуст → `false`.
9. Возвращает `true`, если пересечение разрешённых групп и групп пользователя непусто.

**Где используется:**

| Файл | Назначение |
|---|---|
| `inc/usersauth.functions.php` → `usersauth_loginAs()` | первая проверка перед логином |
| `usersauth.header.first.php` (вариант B) | не рендерить баннер, если права убрали |
| `usersauth.header.tags.php` (вариант A) | не рендерить плашку, если права убрали |
| `usersauth.footer.tags.php` (вариант C) | не рендерить toast, если права убрали |
| `usersauth.users.details.tags.php` | не рендерить кнопку в профиле |
| `usersauth.users.loop.php` | не рендерить кнопку в цикле |
| `usersauth.tools.php` | вторая проверка после `cot_auth` |

### 6.4. `usersauth_loginAs($targetUserId, $countVisit = null)` — полный цикл логина

**Возможные результаты:**

| Результат | Когда |
|---|---|
| `['error' => 'csrf']` | `usersauth_verifyCsrf()` вернул `false` |
| `['error' => 'forbidden']` | `usersauth_isAllowed()` вернул `false` |
| `['error' => 'not_found']` | `$targetUserId < 1` или юзер не найден в БД |
| `['error' => 'self']` | `$targetUserId === Cot::$usr['id']` и ещё не имперсонируем |
| `['error' => 'superadmin']` | Целевой — superadmin, а текущий — нет |
| `['error' => 'auth_failed']` | `cot_user_authorize()` вернул `error` |
| `['success' => true, 'user' => $user]` | Успех |

**Пошаговая детализация:**

1. `usersauth_verifyCsrf()` — не прошёл → `csrf`.
2. `usersauth_isAllowed()` — не прошёл → `forbidden`.
3. `$targetUserId < 1` → `not_found`.
4. Если `$countVisit === null` — читает из `Cot::$cfg['plugin']['usersauth']['count_visit']`. Это позволяет вызывающему коду явно передать `true`/`false`, минуя конфиг.
5. Если `$targetUserId === (int)Cot::$usr['id']` **и** `$_SESSION['usersauth_original']` пуста → `self`. Это защита от потери «якоря» — при уже активной имперсонации переключение на себя разрешено.
6. `SELECT * FROM cot_users WHERE user_id = ? LIMIT 1` — пусто → `not_found`.
7. `user_id` и `user_maingrp` приводятся к `int`.
8. `cot_fillGroupsForUser($user)` — заполняет `$user['groups']` (нужно для проверки `superadmin` и для `cot_user_authorize()`).
9. Если целевой — superadmin, а `Cot::$usr['groups']` не содержит `COT_GROUP_SUPERADMINS` → `superadmin`.
10. Если `$_SESSION['usersauth_original']` пуст — записывает:
    ```
    ['id'         => Cot::$usr['id'],
     'name'       => Cot::$usr['name'],
     'maingrp'    => Cot::$usr['maingrp'],
     'token'      => Cot::$sys['xk'],
     'started_at' => time()]
    ```
    Это делается **только один раз**, чтобы при переключении с target-A на target-B якорь остался исходным администратором.
11. `$_SESSION['usersauth_target'] = ['id' => ..., 'name' => ...]`.
12. Снимок `$prevLastlog`, `$prevLastvisit`, `$prevLogcount`, `$prevLastip`, `$prevToken` из `$user`.
13. `cot_user_authorize($user, true)` — полная авторизация Cotonti:
    - пересчитывает `sid` по `user_password + now`, обновляет `user_sid` / `user_sidtime` при необходимости;
    - обновляет `user_lastlog`, `user_lastvisit`, `user_logcount = user_logcount + 1`, `user_lastip`;
    - ротирует `user_token` (`cot_unique(16)`);
    - устанавливает cookie/session `$sys['site_id']` со значением `base64(user_id:sha1(sid))`;
    - вызывает хуки `users.authorize` и `users.authorize.done`.
14. Если `$result['error']` непусто — `unset($_SESSION['usersauth_target'])`, вернуть `auth_failed`. **Откат visit-полей не выполняется** — авторизация не состоялась, восстанавливать нечего.
15. Если `!$countVisit` — восстанавливает `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip`, **и `user_token`**. Восстановление `user_token` важно, потому что `cot_user_authorize()` его **всегда** ротирует; без отката ломались бы CSRF-токены на открытых вкладках пользователя. `user_sid` восстанавливать не нужно — `cot_user_authorize()` переиспользует его, если он валиден.
16. Пишет в `cot_log` с типом `login`, санитизированными `{ADMIN}`, `{USER}`, `{UID}`. Обращение к языковым строкам — через `Cot::$L` (глобальный `$L` внутри функции недоступен без `global $L`, поэтому используется контейнер `Cot::$L`).
17. Возвращает `['success' => true, 'user' => $user]`.

### 6.5. `usersauth_return()` — возврат в исходный аккаунт

**Возможные результаты:**

| Результат | Когда |
|---|---|
| `['error' => 'csrf']` | CSRF-токен не совпал |
| `['error' => 'no_original']` | `$_SESSION['usersauth_original']['id']` пуст |
| `['error' => 'not_found']` | Юзер с сохранённым ID не найден в БД |
| `['error' => 'auth_failed']` | `cot_user_authorize()` вернул `error` |
| `['success' => true]` | Успех |

**Пошаговая детализация:**

1. `usersauth_verifyCsrf()` — не прошёл → `csrf`.
2. Если `$_SESSION['usersauth_original']['id']` пуст → `no_original`.
3. Загружает `SELECT * FROM cot_users WHERE user_id = ? LIMIT 1`.
4. Если не найден — `usersauth_clearSession()` (полный logout Cotonti), `unset` обеих сессионных переменных плагина, вернуть `not_found`. Без принудительного logout администратор остался бы «заперт» под целевой учёткой без возможности вернуться.
5. `cot_user_authorize($user, true)` — авторизация под оригиналом.
6. `unset($_SESSION['usersauth_original'], $_SESSION['usersauth_target'])` — до проверки результата, чтобы не оставаться в «полу-состоянии».
7. Если `$result['error']` — `usersauth_clearSession()`, вернуть `auth_failed`. Администратор не «застревает» под target'ом, следующий запрос обрабатывается как гость — можно войти заново.
8. Пишет в `cot_log` с типом `return`, санитизированным `{USER}` (через `Cot::$L`).
9. Возвращает `['success' => true]`.

### 6.6. `usersauth_isExpired()` — контроль времени имперсонации

```php
function usersauth_isExpired(): bool
```

**Логика:**

1. Если `$_SESSION['usersauth_original']['started_at']` пуст → `false` (не в состоянии имперсонации).
2. Читает `session_lifetime` из конфига; `0` или отрицательное → `false` (без лимита).
3. Возвращает `(time() − started_at) > session_lifetime`.

**Где используется:**

| Файл | Действие при истечении |
|---|---|
| `usersauth.header.first.php` (вариант B) | вызывает `usersauth_return()`, редиректит |
| `usersauth.header.tags.php` (вариант A) | вызывает `usersauth_return()`, редиректит |
| `usersauth.tools.php` | вызывает `usersauth_return()`, показывает `cot_message`, редиректит в админку |

В `usersauth.footer.tags.php` (вариант C) проверка `usersauth_isExpired()` **не выполняется** — на момент рендера футера заголовки уже отправлены и `cot_redirect()` невозможен. Если администратор выбрал только вариант C — автовозврат произойдёт при следующем открытии админ-страницы плагина или при следующем запросе, в котором сработает `header.first`/`header.user.tags` (если они тоже активны).

### 6.7. `usersauth_safeRedirect($redirectB64)` — валидация redirect

```php
function usersauth_safeRedirect(string $redirectB64): string
```

**Логика:**

1. `$redirectB64 === ''` → `cot_url('index')`.
2. `base64_decode($redirectB64, true)` — strict-mode; `false` или пусто → `cot_url('index')`.
3. Должен начинаться с `/` (внутренний абсолютный путь); иначе → `cot_url('index')`.
4. Отклоняет `//` и `/\` в начале — протокол-относительные URL.
5. Отклоняет любые `\` в строке — попытки обмануть браузеры на Windows.
6. Отклоняет `\r\n` — header injection.
7. Возвращает декодированный путь.

**Применение:** в `usersauth.php` перед `cot_redirect($target)`.

**Что защищено:** атакующий не может через параметр `redirect` увести администратора после успешного логина на внешний домен (Open Redirect), на `javascript:` (XSS в контексте `Location`), на протокол-относительный URL (`//evil.com`), на URL с внедрёнными заголовками.

### 6.8. `usersauth_sanitizeForLog($value)` — санитизация перед логом

```php
function usersauth_sanitizeForLog(string $value): string
{
    $value = preg_replace('/[\r\n\t\x00-\x1F]+/u', ' ', $value) ?? '';

    return mb_substr(trim($value), 0, 120, 'UTF-8');
}
```

**Что делает:**

- режет `\r`, `\n`, `\t` и все контрольные символы (`0x00–0x1F`) — защита от лог-инъекции;
- `trim`;
- ограничивает 120 символами через `mb_substr` — защита от «раздувания» записей.

**Применение:** в `usersauth_loginAs()` и `usersauth_return()` при формировании текста для `cot_log()`.

### 6.9. `usersauth_clearSession()` — принудительный logout

```php
function usersauth_clearSession(): void
{
    $siteId = (string) (Cot::$sys['site_id'] ?? '');
    if ($siteId === '') {
        return;
    }

    unset($_SESSION[$siteId]);
    cot_setcookie($siteId, '', time() - 3600);
}
```

**Что делает:**

- удаляет ключ сессии Cotonti (`$sys['site_id']`) из `$_SESSION`;
- сбрасывает cookie Cotonti в прошлое (с `time() − 3600`) — браузер её удаляет.

**Применение:** в `usersauth_return()` при неудачной авторизации оригинала — чтобы администратор не «застрял» под target'ом.

### 6.10. URL-хелперы

#### `usersauth_url(int $userId, string $redirect = ''): string`

URL логина через **админ-панель**:

```
admin.php?m=other&p=usersauth&a=login&uid={UID}&x={TOKEN}[&redirect={BASE64}]
```

- `$redirect` кодируется в `base64_encode`.
- Всегда добавляет `Cot::$sys['xk']` (XSS-токен Cotonti).

#### `usersauth_publicUrl(int $userId): string`

URL логина с **фронтенда**:

```
index.php?e=usersauth&a=login&uid={UID}&x={TOKEN}
```

Используется в кнопках на странице профиля и в списке пользователей.

#### `usersauth_returnUrl(): string`

URL возврата:

```
index.php?e=usersauth&a=return&x={TOKEN}
```

### 6.11. `usersauth_isImpersonating(): bool`

Возвращает `true`, если одновременно непусты `$_SESSION['usersauth_original']['id']` и `$_SESSION['usersauth_target']['id']`.

### 6.12. Особенность работы с языковыми строками внутри функций

`cot_rc($key, $params)` подставляет `{...}`-плейсхолдеры **только если `$key` — это ключ из массива `$R`**. Для готовых строк из `$L` подстановка **не выполняется** — `cot_rc()` вернёт строку как есть, с литералом `{NAME}`.

Внутри функций глобальный `$L` тоже недоступен без `global $L`. Поэтому:

- языковые строки читаются через `Cot::$L[...]`;
- плейсхолдеры в строках из `$L` подставляются через `str_replace()`.

Пример:

```php
cot_log(
    str_replace(
        ['{ADMIN}', '{USER}', '{UID}'],
        [$adminName, $userName, (string) $user['user_id']],
        Cot::$L['usersauth_log']
    ),
    'usersauth',
    'login',
    'done'
);
```

В шаблонных хуках (они подключаются в глобальной области видимости) `$L` работает, но подстановка `{NAME}` всё равно делается через `str_replace()`, потому что источник — `$L`, а не `$R`.

---

## 7. Хуки Cotonti

Плагин регистрирует **пять обязательных хуков и три баннерных, из которых активен один**.

### Обязательные хуки

| Файл | Хук | Назначение |
|---|---|---|
| `usersauth.tools.php` | `tools` | Основная админ-страница (`admin.php?m=other&p=usersauth`) |
| `usersauth.php` | `standalone` | Публичный контроллер (`index.php?e=usersauth&a=login\|return&...`) |
| `usersauth.users.details.tags.php` | `users.details.tags` | Кнопка «Войти как» в детальной странице пользователя |
| `usersauth.users.loop.php` | `users.loop` | Кнопка «Войти как» в цикле списка пользователей |

### Баннерные хуки — выбрать один

| Файл | Хук | Где появляется баннер |
|---|---|---|
| `usersauth.header.tags.php` | `header.user.tags` | Плашка в шапке сайта (теги `{HEADER_USERSAUTH_*}`) |
| `usersauth.header.first.php` | `header.first` | Строка внутри блока штатных notices Cotonti (`{HEADER_NOTICES}`) |
| `usersauth.footer.tags.php` | `footer.tags` | Плавающий Bootstrap 5 Toast в правом нижнем углу |

Неактивные баннерные файлы переименуйте в `.bak` — иначе они тоже зарегистрируются и баннер появится в нескольких местах одновременно.

### 7.1. `tools` — админ-страница

- **Триггер:** `admin.php?m=other&p=usersauth`.
- **Что делает:**
  - `list($plugRead, $plugWrite, $plugAdmin) = cot_auth('plug', 'usersauth')` — **в локальные переменные**, `Cot::$usr['isadmin']` не перезаписывается;
  - `cot_block($plugAdmin)` — блок при отсутствии admin-права на плагин;
  - `usersauth_isAllowed()` — вторая проверка;
  - проверяет `usersauth_isExpired()`, при истечении автоматически возвращает;
  - обрабатывает `a=login&uid=X` (кнопки в админ-таблице);
  - поиск через `LOCATE()`, лимит 50;
  - рендерит `usersauth.admin.tpl`.

### 7.2. `standalone` — публичный контроллер

- **Триггер:** `index.php?e=usersauth&a=...`.
- **Что делает:**
  - `a=login&uid=X` — `usersauth_loginAs()`, при успехе редирект через `usersauth_safeRedirect()`;
  - `a=return` — `usersauth_return()`, редирект на `index.php`;
  - `cot_check_xg()` — до выполнения всей логики.

### 7.3. `users.details.tags` — кнопка в профиле

- **Триггер:** рендер детальной страницы пользователя.
- **Условия:** `usersauth_isAllowed()`, `targetId >= 1`, `targetId !== Cot::$usr['id']`.
- **Присваивает:**
  - `USERS_DETAILS_USERSAUTH_URL` — URL;
  - `USERS_DETAILS_USERSAUTH_LINK` — `<a class="btn btn-danger btn-sm usersauth-login-as" target="_blank" rel="noopener noreferrer">Войти как «{NAME}»</a>`;
  - `USERS_DETAILS_USERSAUTH_ICON` — иконка `<i class="fa-solid fa-right-to-bracket"></i>` с tooltip.

### 7.4. `users.loop` — кнопка в цикле

- **Триггер:** цикл пользователей (`users.tpl`).
- **Условия:** те же.
- **Присваивает:**
  - `USERS_ROW_USERSAUTH_URL`;
  - `USERS_ROW_USERSAUTH_LINK` — компактная кнопка с `target="_blank" rel="noopener noreferrer"`;
  - `USERS_ROW_USERSAUTH_ICON` — иконка-кнопка.

### 7.5. `header.user.tags` — вариант A (плашка в шапке)

- **Триггер:** рендер шапки сайта.
- **Что делает:**
  - `usersauth_isImpersonating()` → иначе `return`;
  - `usersauth_isExpired()` → `usersauth_return()` + `cot_redirect()`;
  - присваивает теги:
    - `HEADER_USERSAUTH_RETURN_URL` — URL возврата;
    - `HEADER_USERSAUTH_RETURN_LINK` — готовый `<a class="btn btn-warning btn-sm">` с `rel="noopener noreferrer"`;
    - `HEADER_USERSAUTH_CURRENT` — текст «Сейчас вы под {NAME}» с `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` и `str_replace('{NAME}', ...)`.
- **Условие работы хука:** у текущего пользователя должен быть бит `R` на `plug.usersauth` — иначе `cot_getextplugins()` не включит этот файл. Поэтому в `usersauth.setup.php` обязательно `Auth_guests=R`, `Auth_members=R`.

### 7.6. `header.first` — вариант B (notices)

- **Триггер:** `system/header.php`, самый первый хук.
- **Что делает:**
  - `usersauth_isImpersonating()` → иначе `return`;
  - `usersauth_isExpired()` → `usersauth_return()` + `cot_redirect()`;
  - добавляет в `Cot::$out['notices_array']` пару `[returnUrl, title]`. Ядро Cotonti само отрендерит её в `{HEADER_NOTICES}` как `<li><a href="URL">TITLE</a></li>`.
- **Условие работы хука:** в `header.tpl` темы должен быть блок `{HEADER_NOTICES}` или `{HEADER_NOTICES_ARRAY}`.
- **Требование к правам:** как и для любого хука, у текущего пользователя нужен `R` на `plug.usersauth` (поэтому `Auth_guests=R`, `Auth_members=R`).

### 7.7. `footer.tags` — вариант C (Toast)

- **Триггер:** `system/footer.php`, до присваивания `FOOTER_RC`.
- **Что делает:**
  - `usersauth_isImpersonating()` → иначе `return`;
  - формирует HTML-блок `<div class="toast-container position-fixed bottom-0 end-0 p-3"><div class="toast show">…</div></div>` с кнопкой-крестиком `data-bs-dismiss="toast"`;
  - добавляет этот блок к `Cot::$out['footer_rc']`.
- **Условие работы хука:** в `footer.tpl` темы должен быть блок `{FOOTER_RC}`.
- **Зависимость:** Bootstrap 5 JS (bundle) для закрытия Toast по крестику.
- **Ограничение:** `usersauth_isExpired()` здесь не вызывается — автовозврат произойдёт при следующем открытии админ-страницы плагина или при следующем запросе, где сработает `header.first`/`header.user.tags`.

---

## 8. Точки входа (URL-маршруты)

| URL | Обработчик | Назначение |
|---|---|---|
| `admin.php?m=other&p=usersauth` | `usersauth.tools.php` (хук `tools`) | Админ-страница с поиском |
| `admin.php?m=other&p=usersauth&a=login&uid={UID}&x={TOKEN}` | `usersauth.tools.php` | Логин из админки |
| `index.php?e=usersauth&a=login&uid={UID}&x={TOKEN}[&redirect={BASE64}]` | `usersauth.php` (хук `standalone`) | Публичный логин |
| `index.php?e=usersauth&a=return&x={TOKEN}` | `usersauth.php` | Возврат в исходный аккаунт |

Параметр `x` — `Cot::$sys['xk']`. Без него `cot_check_xg()` вернёт ошибку.

Параметр `redirect` — base64 от внутреннего пути. Принимается только `/...` без `//`, `/\`, `\\` и CRLF (см. `usersauth_safeRedirect`). Внешние URL и абсолютные адреса отбрасываются, редирект идёт на `index.php`.

---

## 9. API плагина

Все функции объявлены в `inc/usersauth.functions.php` с `declare(strict_types=1)` и проверкой `defined('COT_CODE') or die('Wrong URL')`.

### 9.1. `usersauth_isAllowed(): bool`

Проверяет право текущего пользователя на инструмент. См. раздел 6.3.

### 9.2. `usersauth_verifyCsrf(): bool`

Проверяет XSS-токен `Cot::$sys['xk']` против параметра `x` из GET. Timing-safe. См. раздел 6.2.

### 9.3. `usersauth_loginAs(int $targetUserId, ?bool $countVisit = null): array`

Полный цикл авторизации. См. раздел 6.4.

**Параметры:**

| Имя | Тип | Описание |
|---|---|---|
| `$targetUserId` | `int` | ID целевого пользователя |
| `$countVisit` | `?bool` | `null` — из конфига; `true` — засчитать; `false` — восстановить снимок |

**Возвращает:** `['error' => code]` либо `['success' => true, 'user' => $user]`.

**Побочные эффекты:** пишет `$_SESSION['usersauth_original']` (однократно) и `$_SESSION['usersauth_target']`, вызывает `cot_user_authorize()`, при `count_visit=0` восстанавливает `user_lastlog`/`user_lastvisit`/`user_logcount`/`user_lastip`/`user_token`, пишет в `cot_log`.

### 9.4. `usersauth_return(): array`

Возврат в исходный аккаунт. См. раздел 6.5.

**Возвращает:** `['error' => code]` либо `['success' => true]`.

**Побочные эффекты:** `cot_user_authorize()`, `unset` обоих сессионных ключей, при неудаче — `usersauth_clearSession()`, пишет в `cot_log`.

### 9.5. `usersauth_url(int $userId, string $redirect = ''): string`

URL логина через админ-панель.

### 9.6. `usersauth_publicUrl(int $userId): string`

URL логина с фронтенда.

### 9.7. `usersauth_returnUrl(): string`

URL возврата.

### 9.8. `usersauth_isImpersonating(): bool`

`true` при активной имперсонации.

### 9.9. `usersauth_isExpired(): bool`

`true`, если `session_lifetime` превышен. См. раздел 6.6.

### 9.10. `usersauth_safeRedirect(string $redirectB64): string`

Валидирует base64 `redirect` и возвращает безопасный URL. См. раздел 6.7.

### 9.11. `usersauth_sanitizeForLog(string $value): string`

Очищает строку от контрольных символов и обрезает до 120 символов. См. раздел 6.8.

### 9.12. `usersauth_clearSession(): void`

Полный logout Cotonti: `unset` сессии и сброс cookie в прошлое. См. раздел 6.9.

### 9.13. Пример использования из стороннего кода

```php
<?php
declare(strict_types=1);

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('usersauth', 'plug');

// Проверить, может ли текущий пользователь пользоваться инструментом
if (usersauth_isAllowed()) {
    // Сгенерировать ссылку для кнопки в своём шаблоне
    $loginUrl = usersauth_publicUrl(152);
    $t->assign('MY_BTN_LOGIN_AS_URL', $loginUrl);
}

// Собственная логика: войти под пользователем 152 без засчёта визита
$result = usersauth_loginAs(152, false);
if (!empty($result['error'])) {
    cot_error('Ошибка: ' . $result['error']);
} else {
    cot_redirect(cot_url('index'));
}
```

---

## 10. Штатные функции Cotonti, используемые плагином

### 10.1. Функции Cotonti

| Функция | Где | Назначение |
|---|---|---|
| `cot_incfile('usersauth', 'plug')` | во всех файлах | Подключить `inc/usersauth.functions.php` |
| `cot_langfile('usersauth', 'plug')` | везде, где нужны `$L[...]` | Загрузить языковой файл |
| `cot_tplfile('usersauth.admin', 'plug')` | `usersauth.tools.php` | Путь к шаблону `tpl/usersauth.admin.tpl` |
| `cot_import($name, $source, $type[, $maxlen])` | везде | Импорт параметров с приведением типа |
| `cot_check_xg()` | `usersauth.php`, `usersauth.tools.php` | Проверка XSS-токена; die при несовпадении |
| `cot_auth('plug', 'usersauth')` | `usersauth.tools.php` | Проверка прав группы на плагин |
| `cot_block($flag)` | `usersauth.tools.php` | Блокировка при отсутствии admin-права |
| `cot_die_message($code, $direct = true)` | `usersauth.php`, `usersauth.tools.php` | Принудительная отдача 403 |
| `cot_error($msg)` | `usersauth.php`, `usersauth.tools.php` | Регистрация ошибки |
| `cot_message($msg)` | `usersauth.php`, `usersauth.tools.php` | Регистрация сообщения |
| `cot_redirect($url)` | `usersauth.php`, `usersauth.tools.php`, banner-хуки | Редирект (завершает выполнение) |
| `cot_url($area, $params, $suffix, $secure)` | везде, где URL | Построить URL |
| `cot_rc_link($url, $text, $attrs)` | шаблонные хуки и админ-шаблон | Построить `<a>` |
| `cot_inputbox($type, $name, $value, $attrs)` | `usersauth.tools.php` | Поле `<input>` |
| `cot_build_group($id)` | `usersauth.tools.php` | Имя группы по ID |
| `cot_user_authorize($user, $remember)` | `usersauth_loginAs()`, `usersauth_return()` | Полная авторизация |
| `cot_fillGroupsForUser(&$user)` | `usersauth_loginAs()` | Заполнить `$user['groups']` |
| `cot_log($text, $type, $group, $status)` | `usersauth_loginAs()`, `usersauth_return()` | Запись в `cot_log` |
| `cot_setcookie($name, $value, $expire, ...)` | `usersauth_clearSession()` | Сброс cookie Cotonti |

### 10.2. Глобальные объекты и константы

| Объект | Где | Что это |
|---|---|---|
| `Cot::$db` | `inc/usersauth.functions.php`, `usersauth.tools.php` | PDO Cotonti. `Cot::$db->users` — имя таблицы пользователей |
| `Cot::$usr` | все файлы с проверками | Текущий пользователь (`id`, `name`, `groups`, `maingrp`, `auth_*`, `isadmin`) |
| `Cot::$cfg` | `usersauth_isAllowed()`, `usersauth_loginAs()`, `usersauth_isExpired()` | Конфигурация (`Cot::$cfg['plugin']['usersauth']`) |
| `Cot::$sys` | `usersauth_*Url()`, `usersauth_verifyCsrf()`, `usersauth_clearSession()` | Системные данные (`xk`, `site_id`) |
| `Cot::$L` | `inc/usersauth.functions.php` | Языковые строки внутри функций (глобальный `$L` в функциях недоступен) |
| `$L` | шаблонные хуки (глобальная область) | Языковые строки в шаблонных хуках |
| `Cot::$out['notices_array']` | `usersauth.header.first.php` (вариант B) | Массив пар `[url, title]` — ядро рендерит в `{HEADER_NOTICES}` |
| `Cot::$out['footer_rc']` | `usersauth.footer.tags.php` (вариант C) | HTML, подключаемый в футер (`{FOOTER_RC}`) |
| `COT_GROUP_SUPERADMINS` | `usersauth_isAllowed()`, `usersauth_loginAs()` | ID группы суперадминистраторов (`5`) |
| `COT_GROUP_DEFAULT / GUESTS / INACTIVE / BANNED` | `usersauth_isAllowed()` | Системные группы (`0`, `1`, `2`, `3`) |
| `COT_CODE` | все файлы | Признак инициализации Cotonti |
| `COT_ADMIN` | `usersauth.tools.php` | Признак «мы в админке» |
| `XTemplate` | `usersauth.tools.php` | Шаблонизатор Cotonti |

---

## 11. Сессионные переменные

Плагин использует **две** сессионные переменные.

### 11.1. `$_SESSION['usersauth_original']`

Заполняется **один раз** — при первом переключении в текущей сессии. Представляет собой «якорь» — куда возвращаемся.

```php
$_SESSION['usersauth_original'] = [
    'id'         => (int) Cot::$usr['id'],       // ID исходного администратора
    'name'       => (string) Cot::$usr['name'],  // логин
    'maingrp'    => (int) Cot::$usr['maingrp'],  // основная группа
    'token'      => (string) Cot::$sys['xk'],    // XSS-токен на момент переключения
    'started_at' => time(),                      // Unix-время начала имперсонации
];
```

Ключ `started_at` используется `usersauth_isExpired()` для контроля `session_lifetime`. Ключ `token` хранится для возможных будущих сценариев проверки.

### 11.2. `$_SESSION['usersauth_target']`

Заполняется при каждом логине. Содержит информацию о том, под кем мы сейчас.

```php
$_SESSION['usersauth_target'] = [
    'id'   => (int) $user['user_id'],
    'name' => (string) $user['user_name'],
];
```

При `usersauth_return()` обе переменные **удаляются**.

---

## 12. Модель безопасности

Плагин использует **только штатные механизмы Cotonti** и добавляет собственные уровни защиты. Ниже — по слоям.

### 12.1. Слой 1: CSRF-защита (двойная)

- **Entry-point:** `cot_check_xg()` в `usersauth.php` и `usersauth.tools.php` — проверяет токен `x` против `$sys['xk']` до выполнения любой логики.
- **Функции:** `usersauth_verifyCsrf()` в `usersauth_loginAs()` и `usersauth_return()` — timing-safe проверка через `hash_equals()`, срабатывает **до** любых проверок прав.

Это защищает от случаев, когда функцию вызывает сторонний плагин, минуя entry-point. Даже если CSRF-токен не передан или неверен — логин/возврат не выполнятся.

### 12.2. Слой 2: Авторизация через `cot_user_authorize()`

Плагин **не устанавливает** cookie, `sid`, XSS-токен своими руками. Он вызывает штатную `cot_user_authorize($user, true)`, которая делает всё то же, что при обычном входе:

- пересчитывает `user_sid` / `user_sidtime` через HMAC-SHA256 с `Cot::$cfg['secret_key']`;
- обновляет `user_token` (CSRF);
- устанавливает cookie Cotonti с `HttpOnly` и опционально `Secure` (при HTTPS);
- вызывает хуки `users.authorize` и `users.authorize.done`.

Это гарантирует совместимость с любыми плагинами, которые подписаны на события входа.

### 12.3. Слой 3: Проверки прав перед действием

- `cot_auth('plug', 'usersauth')` в `usersauth.tools.php` — право `A` на плагин обязательно.
- `cot_block($plugAdmin)` — блокирует при отсутствии admin-права.
- `usersauth_isAllowed()` — суперадмин или группа из `allow_groups`.
- **`Cot::$usr['isadmin']` не перезаписывается** — глобальный флаг суперадмина остаётся корректным для всех последующих проверок.

Итог: чтобы открыть админ-страницу, нужно **одновременно** пройти `cot_auth`, `cot_block` и `usersauth_isAllowed()`.

### 12.4. Слой 4: Запрет опасных комбинаций

- **Имперсонация суперадмина запрещена для не-суперадмина.** Возврат `['error' => 'superadmin']`.
- **Имперсонация себя запрещена при неактивном якоре.** Возврат `['error' => 'self']`.
- **Системные группы игнорируются в `allow_groups`** (`0`, `1`, `2`, `3`).

### 12.5. Слой 5: Защита URL и редиректов

- Параметр `redirect` кодируется в base64 и **строго валидируется** через `usersauth_safeRedirect()`. Принимаются только внутренние пути.
- Отклоняются: абсолютные URL, `//host`, `/\`, `\\`, CRLF.
- Все URL плагина содержат параметр `x=$sys['xk']`.

### 12.6. Слой 6: Защита от XSS

- Все имена пользователей, выводимые в шаблоны, проходят через `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- В шаблонах `target="_blank"` сопровождается `rel="noopener noreferrer"` — защита от tab-napping и referrer-leak (в т.ч. утечки `x`-токена).

### 12.7. Слой 7: Защита от SQL-инъекции и LIKE-DoS

- Все SQL-запросы через prepared statements (`Cot::$db->query($sql, $params)`).
- Поиск через `LOCATE(:needle, column)`, а не `LIKE` — параметр трактуется буквально. `%` и `_` в запросе не работают как wildcard'ы, нет LIKE-инъекции, нет DoS через `%...%`.
- `CAST(user_id AS CHAR) = :exact` — точное сравнение по ID, без приведения типов на стороне SQL-инъекции.

### 12.8. Слой 8: Санитизация логов

`usersauth_sanitizeForLog()` режет `\r\n\t` и `0x00–0x1F`, ограничивает 120 символами. Это защищает от лог-инъекции — например, попытки «внедрить» HTML в имя пользователя и сломать отображение раздела **Admin → Tools → Logs**, или «раздуть» запись многократными переносами.

### 12.9. Слой 9: Восстановление состояния пользователя

При `count_visit=0` после `cot_user_authorize()` восстанавливаются:

- `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip` — статистика не искажается;
- `user_token` — CSRF-токены на открытых вкладках пользователя не ломаются.

### 12.10. Слой 10: Защита от застревания в сессии

- `session_lifetime` — автоматический возврат по истечении (в админке и в баннерных хуках вариантов A и B).
- `usersauth_clearSession()` — полный logout, если оригинального администратора восстановить не удалось (забанен/удалён). Администратор не «застревает» под target'ом.
- `unset($_SESSION['usersauth_target'])` при неудачном `cot_user_authorize()` в `usersauth_loginAs()` — не остаётся «полу-состояния».

### 12.11. Слой 11: Сбалансированные права по умолчанию

`Auth_guests=R`, `Auth_members=R` — минимальный бит чтения, необходимый для того, чтобы `cot_getextplugins()` включал баннерные хуки для имперсонированного пользователя (иначе администратор не увидит кнопку возврата и «застрянет» под чужой учёткой). Право `A` на `plug.usersauth` никому не выдаётся автоматически — оно назначается только явно через **Админ → Права → usersauth** суперадмином.

`Lock_guests=W12345A` и `Lock_members=W12345A` блокируют остальные биты, но не мешают назначить `A` через `admin.php?m=rights`.

### 12.12. Что плагин НЕ защищает

- **Не блокирует** просмотр паролей — их нет в открытом виде в Cotonti.
- **Не защищает** от того, что сам администратор знает свой пароль — вне модели угроз.
- **Не логирует неуспешные попытки** — только успешный логин и успешный возврат.
- **Не ограничивает** число переключений между target'ами в одной сессии — можно свободно переключаться, якорь сохраняется.
- **Не блокирует** параллельную работу под разными учётками в разных браузерах — это вне модели угроз Cotonti-сессий.

---

## 13. Права доступа

Используются две независимые системы проверки, и **обе должны пройти**.

### 13.1. Штатные права Cotonti

При установке плагин регистрируется в `cot_auth` со `auth_code='plug'` и `auth_option='usersauth'`. Маски из `usersauth.setup.php`:

```
Auth_guests=R
Lock_guests=W12345A
Auth_members=R
Lock_members=W12345A
```

Расшифровка масок:

- `0` — нет прав;
- `R` (1) — чтение;
- `W` (2) — запись;
- `1..5` (4/8/16/32/64) — кастомные биты;
- `A` (128) — администратор.

**Итог:** при установке гости и зарегистрированные пользователи получают только бит `R` — это необходимо для того, чтобы `cot_getextplugins()` подключал баннерные хуки во время их сессии. Право `A` на плагин никому не выдаётся — суперадминистраторы (группа 5) проходят по штатной логике Cotonti.

Чтобы дать доступ группе:

**Админ → Права → usersauth** — отметить группу, дать право `A`.

### 13.2. Проверка `usersauth_isAllowed()`

Помимо прав Cotonti:

- гость — нельзя;
- `COT_GROUP_SUPERADMINS` — можно;
- иначе — пересечение `allow_groups` (с фильтрацией системных групп) и `Cot::$usr['groups']`.

### 13.3. Сводная таблица

| Сценарий | `cot_auth` | `isAllowed` | Результат |
|---|---|---|---|
| Суперадмин | ✅ (по штатной логике) | ✅ | Доступ |
| Группа в `allow_groups` + право `A` | ✅ | ✅ | Доступ |
| Группа в `allow_groups`, без права `A` | ❌ | — | 403 от `cot_auth` |
| Группа с правом `A`, без `allow_groups` | ✅ | ❌ | 403 от `isAllowed` |
| Гость | ❌ (только `R`) | — | 403 при попытке входа |
| Неактивный / забанен | ❌ | — | 403 |

### 13.4. Внутренние ограничения

Независимо от прав:

1. **Нельзя войти под суперадминистратором**, если текущий — не суперадмин.
2. **Нельзя войти под самим собой** при неактивном якоре.
3. **Целевой пользователь должен существовать** в `cot_users`.

---

## 14. Логирование

Каждый успешный логин и возврат пишется в `cot_log` через `cot_log($text, $type, $group, $status)`.

**Параметры:**

- `$type` — `'usersauth'`;
- `$group` — `'login'` (логин) или `'return'` (возврат);
- `$status` — `'done'`.

**Тексты:**

| Событие | Ключ | Значение (RU) |
|---|---|---|
| Логин | `Cot::$L['usersauth_log']` | `Администратор {ADMIN} вошёл как пользователь {USER} (ID {UID})` |
| Возврат | `Cot::$L['usersauth_log_return']` | `Администратор вернулся из режима пользователя {USER}` |

**Подстановки:**

- `{ADMIN}` — имя исходного админа из `$_SESSION['usersauth_original']['name']`;
- `{USER}` — имя целевого пользователя;
- `{UID}` — ID целевого пользователя.

Все значения проходят `usersauth_sanitizeForLog()` — лог-инъекция невозможна.

**Просмотр:** **Админ → Инструменты → Логи** или `admin.php?m=other&p=log`.

---

## 15. Интеграция в шаблоны темы

### 15.1. Баннер возврата — три варианта на выбор. Кому как удобно так и интегрировать в свою тему.

Одновременно должен быть активен **один** баннерный файл. Два остальных переименуйте в `.bak` — иначе баннер появится в нескольких местах.

| Вариант | Файл | Что видит пользователь | Что нужно в шаблоне | Зависимости |
|---|---|---|---|---|
| **A. Шапка** | `usersauth.header.tags.php` | Плашка `alert-warning` в шапке | Теги `{HEADER_USERSAUTH_*}` в `header.tpl` | Только Cotonti |
| **B. Notices** | `usersauth.header.first.php` | Строка внутри блока notices | `{HEADER_NOTICES}` (или `{HEADER_NOTICES_ARRAY}`) в `header.tpl` | Только Cotonti |
| **C. Toast** | `usersauth.footer.tags.php` | Плавающий Bootstrap Toast в правом нижнем углу | `{FOOTER_RC}` в `footer.tpl` | Bootstrap 5 JS |

### 15.2. Вариант A — плашка в шапке (`header.user.tags`)

**Теги:**

| Тег | Что содержит |
|---|---|
| `HEADER_USERSAUTH_RETURN_URL` | URL возврата (`index.php?e=usersauth&a=return&x=...`) |
| `HEADER_USERSAUTH_RETURN_LINK` | Готовый `<a class="btn btn-warning btn-sm" rel="noopener noreferrer">` с `title="Вернуться в аккаунт администратора"` |
| `HEADER_USERSAUTH_CURRENT` | Текст «Сейчас вы под {NAME}» (имя экранировано) |

**Пример вставки в `header.tpl`:**

```html
<!-- IF {HEADER_USERSAUTH_RETURN_LINK} -->
<div class="container mt-2">
    <div class="alert alert-warning d-flex align-items-center justify-content-between">
        <span>{HEADER_USERSAUTH_CURRENT}</span>
        {HEADER_USERSAUTH_RETURN_LINK}
    </div>
</div>
<!-- ENDIF -->
```

Проверка `IF {HEADER_USERSAUTH_RETURN_LINK}` означает: плашка показывается только при активной имперсонации.

**Требование:** у имперсонированного пользователя должен быть бит `R` на `plug.usersauth` — иначе `cot_getextplugins()` не подключит `usersauth.header.tags.php`, и плашка не появится. Именно для этого в `usersauth.setup.php` стоит `Auth_guests=R`, `Auth_members=R`.

### 15.3. Вариант B — строка в notices (`header.first`)

Файл добавляет в `Cot::$out['notices_array']` пару `[returnUrl, 'Сейчас вы под {NAME} — Вернуться в свой аккаунт']`. Ядро Cotonti само отрендерит это в блоке `{HEADER_NOTICES}` (или `{HEADER_NOTICES_ARRAY}`) как `<li><a href="URL">TITLE</a></li>`.

**Что нужно в `header.tpl`:** блок `{HEADER_NOTICES}` — стандарт для современных тем Cotonti. Если блока нет, добавьте:

```html
<!-- IF {HEADER_NOTICES} -->
<div class="container mt-2">
    <ul class="list-unstyled">
        {HEADER_NOTICES}
    </ul>
</div>
<!-- ENDIF -->
```

**Что видит пользователь:** стандартная ссылка-notice с текстом «Сейчас вы под Олег — Вернуться в свой аккаунт».

**Преимущества варианта:** никаких кастомных правок шаблона, кроме уже существующего блока notices. Никакого JS, никаких Bootstrap-зависимостей.

### 15.4. Вариант C — Bootstrap 5 Toast (`footer.tags`)

Файл добавляет в `Cot::$out['footer_rc']` блок:

```html
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="usersauth-return-toast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">User Auth Vision</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Сейчас вы под пользователем: {NAME}
            <div class="mt-2">
                <a href="..." class="btn btn-warning btn-sm">Вернуться в свой аккаунт</a>
            </div>
        </div>
    </div>
</div>
```

**Что нужно в `footer.tpl`:** блок `{FOOTER_RC}` — стандарт Cotonti.

**Зависимости:** Bootstrap 5 CSS + JS (bundle, с Popper). Если JS не подключён, крестик работать не будет, но баннер отобразится и ссылка возврата будет кликабельна.

**Ограничение:** `usersauth_isExpired()` здесь не проверяется — автовозврат произойдёт при следующем открытии админ-страницы плагина или при следующем запросе, где сработает `header.first`/`header.user.tags`.

### 15.5. Детальная страница пользователя (`users.details.tpl`)

| Тег | Что содержит |
|---|---|
| `USERS_DETAILS_USERSAUTH_URL` | URL логина |
| `USERS_DETAILS_USERSAUTH_LINK` | `<a class="btn btn-danger btn-sm usersauth-login-as" target="_blank" rel="noopener noreferrer">Войти как «{NAME}»</a>` |
| `USERS_DETAILS_USERSAUTH_ICON` | Иконка `<i class="fa-solid fa-right-to-bracket"></i>` с tooltip |

**Пример вставки в `users.details.tpl`:**

```html
<!-- IF {USERS_DETAILS_USERSAUTH_LINK} -->
<div class="usersauth-actions mt-3">
    {USERS_DETAILS_USERSAUTH_LINK}
    {USERS_DETAILS_USERSAUTH_ICON}
</div>
<!-- ENDIF -->
```

Кнопка рендерится только если:

- текущий пользователь имеет право на плагин (`usersauth_isAllowed()`);
- просматриваемый профиль — **не его собственный**;
- `user_id >= 1`.

### 15.6. Список пользователей (`users.tpl`)

| Тег (в контексте `USERS_ROW`) | Что содержит |
|---|---|
| `USERS_ROW_USERSAUTH_URL` | URL логина |
| `USERS_ROW_USERSAUTH_LINK` | `<a class="btn btn-sm btn-outline-danger usersauth-login-as" target="_blank" rel="noopener noreferrer">Войти как «{NAME}»</a>` |
| `USERS_ROW_USERSAUTH_ICON` | Иконка `<a class="btn btn-sm btn-danger"><i class="fa-solid fa-right-to-bracket"></i></a>` |

**Пример вставки в `users.tpl` (в блоке `USERS_ROW`):**

```html
<!-- BEGIN: USERS_ROW -->
<tr>
    <td>{USERS_ROW_ID}</td>
    <td>{USERS_ROW_NAME}</td>
    <!-- IF {USERS_ROW_USERSAUTH_LINK} -->
    <td class="text-center">{USERS_ROW_USERSAUTH_ICON}</td>
    <!-- ENDIF -->
</tr>
<!-- END: USERS_ROW -->
```

### 15.7. Стандартный шаблон админки (`tpl/usersauth.admin.tpl`)

Блоки:

| Блок | Назначение |
|---|---|
| `MAIN` | Весь контент страницы |
| `MAIN.IMPERSONATING` | Плашка «Сейчас вы под {NAME}» + ссылка возврата |
| `MAIN.SEARCH_RESULTS` | Таблица результатов поиска |
| `MAIN.SEARCH_RESULTS.ROW` | Одна строка таблицы |
| `MAIN.NO_RESULTS` | Сообщение «не найдено», если запрос был, но результатов нет |

**Теги в `MAIN`:**

| Тег | Значение |
|---|---|
| `USERSAUTH_TITLE` | Заголовок |
| `USERSAUTH_HELP` | Подсказка |
| `USERSAUTH_FORM_ACTION` | URL формы (`admin.php?m=other&p=usersauth`) |
| `USERSAUTH_SEARCH` | Поле поиска |
| `USERSAUTH_SUBMIT` | Кнопка Submit |

**Теги в `MAIN.IMPERSONATING`:**

| Тег | Значение |
|---|---|
| `USERSAUTH_CURRENT` | «Сейчас вы под пользователем: {NAME}» (экранировано) |
| `USERSAUTH_RETURN_LINK` | Ссылка возврата |

**Теги в `MAIN.SEARCH_RESULTS.ROW`:**

| Тег | Значение |
|---|---|
| `USERSAUTH_ROW_ID` | `user_id` |
| `USERSAUTH_ROW_NAME` | Логин + `(Имя Фамилия)` если заполнены (экранировано) |
| `USERSAUTH_ROW_EMAIL` | Email (экранировано) |
| `USERSAUTH_ROW_GROUP` | Название группы |
| `USERSAUTH_ROW_URL` | URL логина |
| `USERSAUTH_ROW_PROFILE` | URL профиля |

Все ссылки с `target="_blank"` в шаблоне имеют `rel="noopener noreferrer"`.

---

## 16. Руководство пользователя

### 16.1. Вход под пользователем из админ-панели

1. Откройте **Админ → Инструменты → User Auth Vision** или `admin.php?m=other&p=usersauth`.
2. Введите в поле поиска логин, e-mail или ID.
3. Нажмите **Найти**.
4. В таблице результатов нажмите **Войти под выбранным пользователем** рядом с нужной строкой.
5. Плагин авторизует вас под этим пользователем и перенаправит на главную (`index.php`).
6. В зависимости от выбранного варианта баннера вы увидите:
   - плашку в шапке сайта;
   - строку в блоке notices;
   - плавающий Toast в правом нижнем углу.

### 16.2. Вход под пользователем из профиля

1. Откройте `index.php?m=users&a=details&id={UID}`.
2. Если у вас есть право и это **не ваш** профиль — появится кнопка «Войти как «{NAME}»».
3. Нажмите — откроется новая вкладка, в которой вы уже под этим пользователем. Текущая вкладка остаётся под вашим аккаунтом (cookie сессии станут общими только после перезагрузки).
4. В новой вкладке вы увидите выбранный вариант баннера.

### 16.3. Вход под пользователем из списка

1. Откройте `index.php?m=users`.
2. В каждой строке (кроме своей) появится кнопка **Войти как «{NAME}»** или иконка.
3. Нажмите — откроется новая вкладка.

### 16.4. Возврат в свой аккаунт

**Вручную:** нажмите кнопку **Вернуться в свой аккаунт** в баннере (там, где он у вас появляется) или в админ-панели плагина.

**Автоматически:** по истечении `session_lifetime` (по умолчанию 3600 секунд) плагин вернёт вас при следующем рендере страницы — в админке или в баннерных хуках вариантов A и B. Для варианта C автовозврат произойдёт при следующем открытии админ-страницы плагина.

### 16.5. Настройка засчитывания визита

1. **Админ → Расширения → usersauth → Настройки**.
2. **«Засчитывать как реальный визит»** — по умолчанию выключено (рекомендуется).
3. Включайте только если тестируете плагины, которые читают `lastlog`/`lastvisit`/`logcount`/`lastip`.

### 16.6. Настройка времени жизни сессии

1. **Админ → Расширения → usersauth → Настройки**.
2. **«Время жизни сессии имперсонации (сек.)»** — по умолчанию `3600`.
3. `0` — без ограничений.

### 16.7. Разрешение использования не-суперадминам

1. **Админ → Расширения → usersauth → Настройки** — в поле **«Разрешённые группы»** укажите ID групп через запятую (например `4,6`).
2. **Админ → Права → usersauth** — отметьте ту же группу, выдайте право `A`.
3. Оба условия обязательны.

### 16.8. Что происходит при навигации

- После логина текущая вкладка редиректится на `index.php` или на `redirect`-параметр.
- Кнопки в профиле и списке открываются в новой вкладке (`target="_blank"`), текущая вкладка остаётся под вашим аккаунтом.
- Сессия Cotonti общая для всех вкладок одного браузера — переключение в одной вкладке затронет другие при следующем запросе.
- Для параллельной работы под двумя учётками используйте разные браузеры или приватный режим.

---

## 17. Структура файлов

```
plugins/usersauth/
├── usersauth.setup.php                  # Паспорт плагина, регистрация, конфиг
├── inc/
│   └── usersauth.functions.php          # Ядро: функции usersauth_*
├── usersauth.php                        # Standalone-контроллер (хук standalone)
├── usersauth.tools.php                  # Админ-страница (хук tools)
├── usersauth.header.tags.php            # Вариант A баннера (хук header.user.tags)
├── usersauth.header.first.php           # Вариант B баннера (хук header.first)
├── usersauth.footer.tags.php            # Вариант C баннера (хук footer.tags)
├── usersauth.users.details.tags.php     # Кнопка в профиле (хук users.details.tags)
├── usersauth.users.loop.php             # Кнопка в цикле (хук users.loop)
├── lang/
│   └── usersauth.ru.lang.php            # Русская локализация
└── tpl/
    └── usersauth.admin.tpl              # Шаблон админ-страницы
```

| Файл | Ответственность |
|---|---|
| `usersauth.setup.php` | Паспорт плагина: код, версия, права, конфиг, зависимости. **Не регистрируется как хук** — Cotonti пропускает файлы с суффиксами `setup`, `install`, `uninstall`, `configure` |
| `inc/usersauth.functions.php` | Вся бизнес-логика: проверка прав, CSRF, логин, возврат, URL-хелперы, санитизация, clearSession |
| `usersauth.php` | Публичная точка входа: `a=login`, `a=return` |
| `usersauth.tools.php` | Админ-страница: поиск, таблица, форма, плашка имперсонации |
| `usersauth.header.tags.php` | Баннер, вариант A: теги `HEADER_USERSAUTH_*` |
| `usersauth.header.first.php` | Баннер, вариант B: строка в `Cot::$out['notices_array']` |
| `usersauth.footer.tags.php` | Баннер, вариант C: Bootstrap Toast в `Cot::$out['footer_rc']` |
| `usersauth.users.details.tags.php` | Теги `USERS_DETAILS_USERSAUTH_*` |
| `usersauth.users.loop.php` | Теги `USERS_ROW_USERSAUTH_*` |
| `lang/usersauth.ru.lang.php` | Все тексты UI, названия настроек, сообщения |
| `tpl/usersauth.admin.tpl` | Разметка админ-страницы |

Активным должен быть **один** из трёх баннерных файлов. Остальные переименуйте в `.bak` — иначе Cotonti зарегистрирует все три хука и баннер появится в нескольких местах одновременно.

---

## 18. Решение проблем

### 18.1. Кнопки «Войти как» не появляются в профиле и списке

**Причины:**

1. Текущий пользователь — гость или не проходит `usersauth_isAllowed()`. Проверьте, что вы суперадмин или ваша группа добавлена в `allow_groups`.
2. Вы смотрите **свой собственный** профиль — кнопка для своего профиля не рендерится (защита от потери якоря).
3. Шаблон темы не содержит соответствующих тегов. Добавьте `{USERS_DETAILS_USERSAUTH_LINK}` и `{USERS_ROW_USERSAUTH_LINK}` в шаблоны (см. раздел 15).

### 18.2. Баннер возврата не появляется после входа под пользователем

Проверьте по варианту баннера.

**Вариант A — плашка в шапке:**

1. В `header.tpl` есть блок `IF {HEADER_USERSAUTH_RETURN_LINK}` (раздел 15.2).
2. В `usersauth.setup.php` параметры `Auth_guests=R` и `Auth_members=R` — без бита `R` на `plug.usersauth` `cot_getextplugins()` не включает `usersauth.header.tags.php` для имперсонированного пользователя.
3. Файл `usersauth.header.tags.php` активен (не переименован в `.bak`).

**Вариант B — notices:**

1. В `header.tpl` есть блок `{HEADER_NOTICES}` или `{HEADER_NOTICES_ARRAY}`.
2. Файл `usersauth.header.first.php` активен.
3. В `usersauth.setup.php` есть `Auth_guests=R`, `Auth_members=R`.

**Вариант C — Toast:**

1. В `footer.tpl` есть блок `{FOOTER_RC}`.
2. Файл `usersauth.footer.tags.php` активен.
3. Bootstrap 5 CSS+JS подключён в теме.
4. В `usersauth.setup.php` есть `Auth_guests=R`, `Auth_members=R`.

**Общая причина для всех трёх вариантов:** имперсонация не активна. Плагин заполняет баннер только при `usersauth_isImpersonating() = true`.

### 18.3. В баннере виден литерал `{NAME}` вместо имени пользователя

`cot_rc($string, $params)` подставляет `{...}`-плейсхолдеры **только для ключей `$R`**, не для `$L`. В баннерных файлах и в логах плагин использует `str_replace('{NAME}', ...)` напрямую. Если вы правили код плагина и вернули `cot_rc(...)` для строки из `$L` — замените обратно на `str_replace`.

Проверка:

```php
var_dump(cot_rc('Войти как «{NAME}»', ['NAME' => 'Иван']));
// → string(31) "Войти как «{NAME}»"  ← плейсхолдер НЕ подставлен

var_dump(str_replace('{NAME}', 'Иван', 'Войти как «{NAME}»'));
// → string(23) "Войти как «Иван»"
```

### 18.4. Ошибка «Undefined variable $L» или «Trying to access array offset on null» в `usersauth.functions.php`

Внутри функций глобальный `$L` не виден. Обращение к языковым строкам внутри `usersauth_loginAs()` и `usersauth_return()` должно идти через `Cot::$L[...]`, а не через `$L[...]`.

В шаблонных хуках (`usersauth.header.first.php`, `usersauth.header.tags.php`, `usersauth.footer.tags.php`, `usersauth.users.details.tags.php`, `usersauth.users.loop.php`) `$L` работает, потому что эти файлы подключаются в глобальной области видимости через `system/plugin.php` и `system/header.php`/`system/footer.php`.

### 18.5. Ошибка «У вас нет прав на использование этого инструмента»

Сработала `usersauth_isAllowed()`. Решение: добавьте вашу группу в `allow_groups`.

### 18.6. Ошибка 403 при открытии страницы плагина в админке

Сработала `cot_auth('plug', 'usersauth')` или `cot_block($plugAdmin)`. Решение: в **Админ → Права → usersauth** выдайте группе право `A` на плагин.

### 18.7. Ошибка «Вход под другим суперадминистратором запрещён»

Вы не суперадминистратор и пытаетесь войти под суперадминистратором. Это защита от повышения привилегий. Войдите под суперадмином сами — либо попросите суперадмина выполнить действие.

### 18.8. Ошибка «Нельзя войти под самим собой»

Вы нажали кнопку под своим собственным ID, находясь в обычном состоянии. Откройте чужой профиль. Если вы уже имперсонируете — эта ошибка не возникает.

### 18.9. Ошибка «Ошибка безопасности (CSRF)»

CSRF-токен не совпал или отсутствует в URL. Проверьте, что ссылка содержит параметр `x=$sys['xk']`. Обновите страницу и повторите. Если вы вызываете `usersauth_loginAs()` из своего кода — не забудьте передать `x` в URL или вызовите `cot_check_xg()` в своём entry-point.

### 18.10. Ошибка «Не удалось выполнить авторизацию»

`cot_user_authorize()` вернул ошибку. Возможные причины:

- целевой пользователь забанен (`COT_GROUP_BANNED`);
- целевой пользователь неактивен (`COT_GROUP_INACTIVE`) и `inactive_login` выключен в конфиге Cotonti;
- оригинального администратора удалили/забанили — тогда сессия будет принудительно закрыта, нужно залогиниться заново.

### 18.11. Сессия имперсонации истекла

По истечении `session_lifetime` вы автоматически вернулись в свой аккаунт (в админке и в баннерных хуках вариантов A и B). Если хотите дольше — увеличьте `session_lifetime` в настройках (или установите `0` для отключения лимита). Для варианта C автовозврат произойдёт при следующем открытии админ-страницы плагина.

### 18.12. Не удаётся вернуться в аккаунт администратора

**Причины:**

1. Оригинального администратора удалили/забанили — плагин принудительно закрыл вашу сессию, нужно залогиниться заново.
2. Сессия истекла (долго не заходили), cookies очищены или сменился браузер.
3. Якорь (`$_SESSION['usersauth_original']`) уничтожен вручную.

**Решение:** залогиньтесь в админку обычным способом — вы автоматически окажетесь под своим аккаунтом.

### 18.13. Поиск в админке ничего не находит

Используется `LOCATE()` — поиск по подстроке, **без wildcard-символов**. Введите подстроку логина/email или точный ID. Символ `%` будет искаться буквально.

### 18.14. Сессия не переключается между вкладками

Это штатное поведение Cotonti. Сессия общая для всех вкладок одного браузера. Кнопки плагина имеют `target="_blank"` — открывают новую вкладку, но сессия общая.

**Решение:** для параллельной работы под двумя учётками — разные браузеры или приватный режим.

### 18.15. Баннер появился в нескольких местах одновременно

Активны несколько баннерных файлов. Оставьте один, остальные переименуйте в `.bak`:

- `usersauth.header.tags.php` — вариант A;
- `usersauth.header.first.php` — вариант B;
- `usersauth.footer.tags.php` — вариант C.

После переименования — **Админ → Расширения → usersauth → Обновить** и очистка кэша.

---

## 19. Глоссарий

| Термин | Что означает |
|---|---|
| **Имперсонация** | Процесс входа под другой учётной записью. Плагин выполняет полноценную авторизацию от имени целевого пользователя. |
| **Якорь** | Запись `$_SESSION['usersauth_original']` — исходный аккаунт администратора, к которому возвращаемся по `usersauth_return()`. |
| **Целевой пользователь** | Пользователь, под которым выполняется логин (`$targetUserId` / `$uid`). |
| **`cot_user_authorize()`** | Штатная функция Cotonti: полная авторизация (cookie, sid, XSS, `user_token`, `lastlog`). |
| **`cot_fillGroupsForUser()`** | Штатная функция Cotonti: заполняет `$user['groups']` (массив ID групп). |
| **XSS-токен (`Cot::$sys['xk']`)** | Токен-защита от CSRF. Передаётся в URL как `x`. Проверяется `cot_check_xg()` и `usersauth_verifyCsrf()`. |
| **`cot_log()`** | Штатная функция для записи в `cot_log`. Плагин пишет с типом `usersauth`, группой `login` или `return`. |
| **`hash_equals()`** | PHP-функция timing-safe сравнения строк. Используется в `usersauth_verifyCsrf()` для защиты от атак по времени. |
| **`session_lifetime`** | Настройка плагина: время жизни сессии имперсонации в секундах. |
| **`count_visit`** | Настройка: засчитывать визит или восстанавливать `lastlog`/`lastvisit`/`logcount`/`lastip`/`user_token`. |
| **`allow_groups`** | Настройка: список ID групп, которым разрешён доступ. Системные группы `0/1/2/3` игнорируются. |
| **`usersauth_safeRedirect()`** | Функция валидации base64-`redirect`: пропускает только внутренние пути. |
| **`usersauth_sanitizeForLog()`** | Очищает строку от контрольных символов, обрезает до 120. |
| **`usersauth_clearSession()`** | Полный logout Cotonti при неудачном возврате. |
| **`usersauth_isExpired()`** | Проверка превышения `session_lifetime`. |
| **`usersauth_verifyCsrf()`** | Внутренняя timing-safe проверка `x`-токена. |
| **`header.user.tags`** | Хук Cotonti: рендер блока пользователя в шапке темы. Используется вариантом A. |
| **`header.first`** | Хук Cotonti: самый первый хук `system/header.php`. Используется вариантом B. |
| **`footer.tags`** | Хук Cotonti: до присваивания `{FOOTER_RC}` в `system/footer.php`. Используется вариантом C. |
| **`users.details.tags`** | Хук Cotonti: рендер детальной страницы пользователя. |
| **`users.loop`** | Хук Cotonti: цикл по пользователям (список). |
| **`tools`** | Хук Cotonti: страница инструментов в админ-панели. |
| **`standalone`** | Хук Cotonti: публичные контроллеры (`index.php?e=code`). |
| **`{HEADER_NOTICES}`** | Штатный блок notices Cotonti в `header.tpl`. Используется вариантом B. |
| **`{FOOTER_RC}`** | Штатный блок ресурсов в `footer.tpl`. Используется вариантом C. |
| **Bootstrap Toast** | Компонент Bootstrap 5 для всплывающих уведомлений. Требует `bootstrap.bundle.min.js`. |
| **`LOCATE()`** | SQL-функция поиска подстроки без интерпретации `%`/`_` — используется в поиске для защиты от LIKE-инъекции. |
| **`rel="noopener noreferrer"`** | Атрибут `<a target="_blank">`, предотвращающий tab-napping и утечку Referer. |
| **`Cot::$usr['isadmin']`** | Глобальный флаг суперадминистратора Cotonti. Плагин **не перезаписывает** его — использует локальные переменные. |
| **`Cot::$cfg['plugin']['usersauth']`** | Массив настроек плагина из `cot_config`. |
| **`Cot::$L`** | Языковые строки, доступные из любой области видимости. Используется внутри функций плагина. |
| **`COT_GROUP_SUPERADMINS`** | Константа ID группы суперадминистраторов (`5`). |
| **`COT_GROUP_DEFAULT / GUESTS / INACTIVE / BANNED`** | Системные группы (`0`, `1`, `2`, `3`), отсекаемые в `allow_groups`. |
| **Право `A`** | Бит администратора в масках Cotonti (значение `128`). |
| **Право `R`** | Бит чтения в масках Cotonti (значение `1`). |
| **BC (backward compatibility)** | Обратная совместимость — не применяется, все функции плагина собственные. |

---

## 20. Лицензия и ссылки

**Лицензия:** BSD. См. заголовки файлов плагина и файл `LICENSE` в репозитории.

```
Copyright (c) 2026 webitproff
https://github.com/webitproff/usersauth-cotonti
```

**Автор:** [webitproff](https://github.com/webitproff)

**Ссылки:**

- 🏠 Репозиторий: https://github.com/webitproff/usersauth-cotonti
- 🛒 Маркетплейс: https://abuyfile.com/market/cotonti/plugs/usersauth
- 💬 Поддержка: https://abuyfile.com/forums/cotonti/custom/
- 🌐 Cotonti CMF: https://github.com/Cotonti/Cotonti
- 📖 Cotonti Docs: https://www.cotonti.com/
