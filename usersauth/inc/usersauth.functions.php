<?php
declare(strict_types=1);

/**
 * User Auth Vision — core business logic.
 *
 * File: plugins/usersauth/inc/usersauth.functions.php
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @copyright Copyright (c) 2026 webitproff | https://github.com/webitproff/usersauth-cotonti
 * @license BSD
 *
 * ═════════════════════════════════════════════════════════════════════
 *   НАЗНАЧЕНИЕ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Файл содержит всю бизнес-логику плагина: проверки прав и CSRF,
 * авторизацию под целевым пользователем, возврат в исходный аккаунт,
 * контроль времени жизни сессии имперсонации, санитизацию логов,
 * аварийный logout и построение URL. Подключается через
 * `cot_incfile('usersauth', 'plug')` из entry-point'ов и хуков.
 *
 * Все функции объявлены как обычные (не методы класса) и имеют
 * `declare(strict_types=1)` на уровне файла. Прямой вызов без
 * `COT_CODE` заблокирован строкой `defined('COT_CODE') or die('Wrong URL');`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПОДКЛЮЧЕНИЕ ЯЗЫКОВОГО ФАЙЛА
 * ═════════════════════════════════════════════════════════════════════
 *
 * В начале файла выполняется:
 *
 *     require_once cot_langfile('usersauth', 'plug');
 *
 * Это сделано намеренно: `usersauth_loginAs()` и `usersauth_return()`
 * строят тексты для `cot_log()` через `Cot::$L['usersauth_log']` и
 * `Cot::$L['usersauth_log_return']`, и не должны зависеть от того,
 * загрузил ли вызывающий код языковой файл.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СОГЛАШЕНИЯ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   - Все входные параметры типизированы; возвращаемые типы объявлены.
 *   - `Cot::$usr['isadmin']` в этом файле не перезаписывается.
 *   - Запись в лог — только `usersauth_loginAs()` и `usersauth_return()`,
 *     тип `usersauth`, группы `login` и `return`, статус `done`.
 *   - Строки для `cot_log()` формируются через `str_replace()` по
 *     плейсхолдерам `{ADMIN}`, `{USER}`, `{UID}` (не через `cot_rc()` —
 *     источник строк `$L`, а не `$R`).
 *   - Все SQL-запросы идут через `Cot::$db->query(...)` / `Cot::$db->update(...)`
 *     с параметрами (prepared statements).
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СВЯЗАННЫЕ ССЫЛКИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Repository:       https://github.com/webitproff/usersauth-cotonti
 *   Marketplace:      https://abuyfile.com/market/cotonti/plugs/usersauth
 *   Support:          https://abuyfile.com/forums/cotonti/custom/
 *   Cotonti:          https://github.com/Cotonti/Cotonti
 */
 
 
/* 
## Справочник функций `inc/usersauth.functions.php`

Полный перечень по коду файла: 12 функций. Для каждой — сигнатура, назначение, параметры, возврат, побочные эффекты, ключевые детали.

### 1. `usersauth_isAllowed(): bool`

- **Назначение.** Проверяет, имеет ли текущий пользователь право пользоваться инструментом.
- **Параметры.** Нет.
- **Возврат.** `true` — если пользователь суперадмин **или** состоит хотя бы в одной группе из `allow_groups`, при этом группа не системная. Иначе `false`.
- **Побочных эффектов нет.**
- **Порядок проверок по коду:**
  1. `(int) Cot::$usr['id'] < 1` → `false` (гость).
  2. `Cot::$usr['groups']` приводится к массиву `int`, отбрасываются `<= 0`; если массив пуст → `false`.
  3. Если `COT_GROUP_SUPERADMINS` в массиве → `true`.
  4. `allow_groups` обрезается `trim`; пусто → `false`.
  5. Разбивается по `,`, приводится к `int`, отбрасываются `<= 0`.
  6. Из результата удаляются системные `COT_GROUP_DEFAULT`, `COT_GROUP_GUESTS`, `COT_GROUP_INACTIVE`, `COT_GROUP_BANNED`.
  7. Если после фильтра массив пуст → `false`.
  8. `array_intersect($allowedGroups, $userGroups) !== []`.

### 2. `usersauth_verifyCsrf(): bool`

- **Назначение.** Timing-safe проверка CSRF/`x`-токена внутри функций `loginAs()` и `return()` (defence-in-depth — независимо от `cot_check_xg()` в entry-point'ах).
- **Параметры.** Нет. Читает `x` из GET через `cot_import('x', 'G', 'ALP')`.
- **Возврат.** `true`, если `Cot::$sys['xk']` непуст и `hash_equals($expected, $token)` истинно.
- **Побочных эффектов нет.**
- **Детали.** `hash_equals()` — timing-safe. Пустой ожидаемый токен → `false`.

### 3. `usersauth_sanitizeForLog(string $value): string`

- **Назначение.** Очистка значения перед записью в лог: снятие `\r`, `\n`, `\t`, всех контрольных символов `0x00–0x1F`, trim, ограничение длины 120 символов UTF-8.
- **Параметры.** `$value` — исходная строка.
- **Возврат.** Очищенная строка длиной ≤ 120.
- **Побочных эффектов нет.**
- **Детали.** Реализация:
  - `preg_replace('/[\r\n\t\x00-\x1F]+/u', ' ', $value) ?? ''`;
  - `mb_substr(trim(...), 0, 120, 'UTF-8')`.

### 4. `usersauth_clearSession(): void`

- **Назначение.** Принудительный logout Cotonti — применяется, когда оригинального администратора не удаётся восстановить (удалён / забанен / `cot_user_authorize()` вернул ошибку).
- **Параметры.** Нет.
- **Возврат.** `void`.
- **Побочные эффекты:**
  - `unset($_SESSION[Cot::$sys['site_id']])`;
  - `cot_setcookie($siteId, '', time() - 3600)` — cookie гасится в прошлое.
- **Детали.** Если `Cot::$sys['site_id']` пуст — ранний `return`. `cot_setcookie()` вызывается с тремя аргументами; `path`, `domain`, `secure`, `httponly` берутся из значений по умолчанию (см. ядро `system/functions.php`). `user_sid` в БД не сбрасывается.

### 5. `usersauth_loginAs(int $targetUserId, ?bool $countVisit = null): array`

- **Назначение.** Полный цикл авторизации под целевым пользователем.
- **Параметры:**
  - `$targetUserId` — ID целевого пользователя;
  - `$countVisit` — `null` → берётся из `Cot::$cfg['plugin']['usersauth']['count_visit']` (через `!empty(...)`); `true` → визит засчитывается; `false` — снимок восстанавливается.
- **Возврат.** `['error' => string]` или `['success' => true, 'user' => array]`. Возможные `error`: `csrf`, `forbidden`, `not_found`, `self`, `superadmin`, `auth_failed`.
- **Побочные эффекты:**
  - пишет `$_SESSION['usersauth_original']` **однократно** (при пустом текущем значении) с полями `id, name, maingrp, token, started_at`;
  - перезаписывает `$_SESSION['usersauth_target']`;
  - вызывает `cot_fillGroupsForUser($user)`;
  - вызывает `cot_user_authorize($user, true)` — полная авторизация Cotonti;
  - при `count_visit=false` восстанавливает `user_lastlog`, `user_lastvisit`, `user_logcount`, `user_lastip`, `user_token` (все 5 полей);
  - при ошибке `cot_user_authorize()` — `unset($_SESSION['usersauth_target'])`; `$_SESSION['usersauth_original']` не снимается;
  - пишет запись в `cot_log` (`type='usersauth'`, `group='login'`, `status='done'`) с плейсхолдерами `{ADMIN}`, `{USER}`, `{UID}` через `str_replace()`, значения предварительно прогоняются через `usersauth_sanitizeForLog()`.
- **Порядок проверок по коду:**
  1. `usersauth_verifyCsrf()` → `csrf`;
  2. `usersauth_isAllowed()` → `forbidden`;
  3. `$targetUserId < 1` → `not_found`;
  4. `$countVisit === null` → из конфига;
  5. `$targetUserId === (int) Cot::$usr['id']` **и** `empty($_SESSION['usersauth_original'])` → `self`;
  6. `SELECT * FROM <users> WHERE user_id = ? LIMIT 1` → пусто → `not_found`;
  7. `user_id`, `user_maingrp` приводятся к `int`;
  8. `cot_fillGroupsForUser($user)`;
  9. если в `$user['groups']` есть `COT_GROUP_SUPERADMINS`, а в `Cot::$usr['groups']` — нет → `superadmin`;
  10. запись якоря (если ещё не записан) и `usersauth_target`;
  11. снимок visit/token-полей;
  12. `cot_user_authorize($user, true)` → при ошибке `unset` target и `auth_failed`;
  13. при `!$countVisit` — `Cot::$db->update(...)` всех 5 полей обратно;
  14. `cot_log(...)`;
  15. `return ['success' => true, 'user' => $user]`.
- **Детали, важные для корректности:**
  - `user_token` включается в снимок, потому что `cot_user_authorize()` всегда его ротирует; без отката ломаются CSRF-токены открытых вкладок пользователя.
  - `user_sid`/`user_sidtime` в снимок **не входят** и не восстанавливаются: по коду ядра `cot_user_authorize()` переиспользует `user_sid`, если он ещё валиден по `user_sidtime + cookielifetime`.
  - `$_SESSION['usersauth_original']['started_at']` — `time()` на момент первой установки якоря; используется `usersauth_isExpired()`.

### 6. `usersauth_return(): array`

- **Назначение.** Возврат в исходный аккаунт администратора.
- **Параметры.** Нет.
- **Возврат.** `['error' => string]` или `['success' => true]`. Возможные `error`: `csrf`, `no_original`, `not_found`, `auth_failed`.
- **Побочные эффекты:**
  - `cot_user_authorize($original, true)`;
  - `unset($_SESSION['usersauth_original'], $_SESSION['usersauth_target'])` — **до** проверки результата `cot_user_authorize()`;
  - при неудаче — `usersauth_clearSession()` (полный logout Cotonti);
  - пишет в `cot_log` (`type='usersauth'`, `group='return'`, `status='done'`) с `{USER}`, подставляемым из снимка `$targetName` (читается **до** `unset`), санитизируется.
- **Порядок проверок по коду:**
  1. `usersauth_verifyCsrf()` → `csrf`;
  2. `empty($_SESSION['usersauth_original']['id'])` → `no_original`;
  3. `$targetName` запоминается из `$_SESSION['usersauth_target']['name']`;
  4. `SELECT * FROM <users> WHERE user_id = ? LIMIT 1` по `original.id`;
  5. не найден → `usersauth_clearSession()`, `unset` обоих ключей, `not_found`;
  6. `cot_user_authorize($user, true)`;
  7. `unset` обоих ключей плагина;
  8. ошибка авторизации → `usersauth_clearSession()`, `auth_failed`;
  9. `cot_log(...)`;
  10. `return ['success' => true]`.

### 7. `usersauth_url(int $userId, string $redirect = ''): string`

- **Назначение.** Построить URL входа через админ-панель.
- **Параметры:**
  - `$userId` — ID целевого пользователя;
  - `$redirect` — необязательный внутренний путь; если непуст, добавляется в query как `redirect` = `base64_encode($redirect)`.
- **Возврат.** URL от `cot_url('admin', $params, '', true)` с параметрами `m=other`, `p=usersauth`, `a=login`, `uid=$userId`, `x=Cot::$sys['xk']` (+ опционально `redirect`).
- **Побочных эффектов нет.**
- **Детали.** По коду возвращает полный (secure=true) URL; соответствие с README (`admin.php?m=other&p=usersauth&a=login&uid=...&x=...`) — по конвенции `cot_url` для `admin`.

### 8. `usersauth_publicUrl(int $userId): string`

- **Назначение.** Построить публичный (фронтенд) URL входа.
- **Параметры.** `$userId` — ID целевого пользователя.
- **Возврат.** URL от `cot_url('usersauth', ['a'=>'login', 'uid'=>$userId, 'x'=>Cot::$sys['xk']], '', true)`.
- **Побочных эффектов нет.**
- **Детали.** По коду — `cot_url('usersauth', ...)`, а не `cot_url('plug', ['e'=>'usersauth', ...])`. Соответствие с README (`index.php?e=usersauth&a=login&uid=...&x=...`) — по конвенции ядра `cot_url($area, ...)` для расширений; в README этот URL описан именно так.

### 9. `usersauth_returnUrl(): string`

- **Назначение.** Построить URL возврата.
- **Параметры.** Нет.
- **Возврат.** URL от `cot_url('usersauth', ['a'=>'return', 'x'=>Cot::$sys['xk']], '', true)`.
- **Побочных эффектов нет.**

### 10. `usersauth_isImpersonating(): bool`

- **Назначение.** Признак активной имперсонации.
- **Параметры.** Нет.
- **Возврат.** `true`, если одновременно непусты `$_SESSION['usersauth_original']['id']` и `$_SESSION['usersauth_target']['id']`.
- **Побочных эффектов нет.**

### 11. `usersauth_isExpired(): bool`

- **Назначение.** Признак истечения срока жизни сессии имперсонации.
- **Параметры.** Нет.
- **Возврат.** `true`, если `session_lifetime > 0` и `time() - started_at > session_lifetime` (строго больше, не «≥»).
- **Побочных эффектов нет.**
- **Детали.**
  - Пустой `started_at` → `false`.
  - `session_lifetime` читается из `Cot::$cfg['plugin']['usersauth']['session_lifetime']`; отсутствие ключа трактуется как `3600` (`?? 3600`), приведение `(int)`.
  - `<= 0` → `false` (без лимита).

### 12. `usersauth_safeRedirect(string $redirectB64): string`

- **Назначение.** Валидация base64-`redirect` и возврат безопасного URL для редиректа. В этом файле **не вызывается**; по README используется в `usersauth.php` перед `cot_redirect()`.
- **Параметры.** `$redirectB64` — значение `redirect` из query (base64).
- **Возврат.** Безопасный URL или `cot_url('index')`.
- **Побочных эффектов нет.**
- **Правила валидации по коду:**
  1. `$redirectB64 === ''` → `cot_url('index')`;
  2. `base64_decode($redirectB64, true)` — strict; `false` или пусто → `cot_url('index')`;
  3. должно начинаться с `/`; иначе → `cot_url('index')`;
  4. отклоняются `//...` и `/\...` в начале;
  5. отклоняется любой `\` в строке;
  6. отклоняется `[\r\n]`;
  7. возврат `$decoded` как есть.

---

## Сводная таблица

| # | Функция | Параметры | Возврат | Читает сессию | Пишет сессию | Пишет в БД | Пишет в лог | Куда вызывается (по коду файлов) |
|---|---|---|---|---|---|---|---|---|
| 1 | `usersauth_isAllowed` | — | `bool` | — | — | — | — | внутри `loginAs`, в tools и во всех шаблонных хуках |
| 2 | `usersauth_verifyCsrf` | — | `bool` | — | — | — | — | внутри `loginAs`, `return` |
| 3 | `usersauth_sanitizeForLog` | `string` | `string` | — | — | — | — | внутри `loginAs`, `return` |
| 4 | `usersauth_clearSession` | — | `void` | `$_SESSION[site_id]` | `unset` | — | — | внутри `return` |
| 5 | `usersauth_loginAs` | `int, ?bool` | `array` | `usersauth_original` | `usersauth_original`, `usersauth_target` | `UPDATE <users> SET lastlog/lastvisit/logcount/lastip/token` (при `count_visit=false`); через `cot_user_authorize()` — `lastip, lastlog, logcount+1, token, sid, sidtime` | `cot_log(..., 'login', 'done')` | `usersauth.php`, `usersauth.tools.php` |
| 6 | `usersauth_return` | — | `array` | `usersauth_original`, `usersauth_target` | `unset` обоих | через `cot_user_authorize()` — апдейт полей оригинала | `cot_log(..., 'return', 'done')` | `usersauth.php`, `usersauth.tools.php`, баннерные хуки (по README) |
| 7 | `usersauth_url` | `int, string` | `string` | — | — | — | — | не вызывается в этом файле;  для кнопок в админ-таблице |
| 8 | `usersauth_publicUrl` | `int` | `string` | — | — | — | — |  `users.details.tags`, `users.loop` |
| 9 | `usersauth_returnUrl` | — | `string` | — | — | — | — | `usersauth.tools.php` |
| 10 | `usersauth_isImpersonating` | — | `bool` | `usersauth_original`, `usersauth_target` | — | — | — | `usersauth.tools.php`, баннерные хуки |
| 11 | `usersauth_isExpired` | — | `bool` | `usersauth_original` | — | — | — | `usersauth.tools.php`, `header.first`, `header.user.tags` |
| 12 | `usersauth_safeRedirect` | `string` | `string` | — | — | — | — |  `usersauth.php` |

 */
 
 
defined('COT_CODE') or die('Wrong URL');

// Language file is loaded here (not only in entry-point files) so that
// `usersauth_loginAs()` and `usersauth_return()` can build their log messages
// without relying on the caller having loaded it.
require_once cot_langfile('usersauth', 'plug');

/**
 * Check whether the current user is allowed to use the tool.
 *
 * A user is considered allowed when either:
 *   - they belong to `COT_GROUP_SUPERADMINS`; or
 *   - they belong to any group listed in the `allow_groups` setting AND that
 *     group is not a system group.
 *
 * System groups (Default, Guests, Inactive, Banned) are deliberately filtered
 * out of `allow_groups` even if the administrator accidentally puts them there.
 *
 * @return bool
 */
function usersauth_isAllowed(): bool
{
    $userId = (int) (Cot::$usr['id'] ?? 0);
    if ($userId < 1) {
        return false;
    }

    // Normalize the caller's groups: int array, no zeros, no duplicates.
    $userGroups = array_map('intval', (array) (Cot::$usr['groups'] ?? []));
    $userGroups = array_values(array_filter($userGroups, static fn (int $g): bool => $g > 0));
    if ($userGroups === []) {
        return false;
    }

    // Super-admins always pass.
    if (in_array(COT_GROUP_SUPERADMINS, $userGroups, true)) {
        return true;
    }

    $allowedRaw = trim((string) (Cot::$cfg['plugin']['usersauth']['allow_groups'] ?? ''));
    if ($allowedRaw === '') {
        return false;
    }

    $allowedGroups = array_map('intval', explode(',', $allowedRaw));
    $allowedGroups = array_values(array_filter($allowedGroups, static fn (int $g): bool => $g > 0));

    // System groups must never be usable even if entered by mistake.
    $forbidden = [COT_GROUP_DEFAULT, COT_GROUP_GUESTS, COT_GROUP_INACTIVE, COT_GROUP_BANNED];
    $allowedGroups = array_filter(
        $allowedGroups,
        static fn (int $g): bool => !in_array($g, $forbidden, true)
    );

    if ($allowedGroups === []) {
        return false;
    }

    return array_intersect($allowedGroups, $userGroups) !== [];
}

/**
 * Verify the XSS-guard token (`x`) against `Cot::$sys['xk']`.
 *
 * Unlike `cot_check_xg()`, which dies immediately, this function returns a
 * boolean. It is used internally by `usersauth_loginAs()` and
 * `usersauth_return()` as a defence-in-depth check: even if some third-party
 * code calls those functions directly, no state change happens without a valid
 * token.
 *
 * The comparison uses `hash_equals()` — timing-safe by design.
 *
 * @return bool TRUE when the provided token matches the current XSS token.
 */
function usersauth_verifyCsrf(): bool
{
    $token    = (string) cot_import('x', 'G', 'ALP');
    $expected = (string) (Cot::$sys['xk'] ?? '');

    return $expected !== '' && hash_equals($expected, $token);
}

/**
 * Strip control characters and cap the length of a value before writing it
 * to the log.
 *
 * Prevents log-injection (CR/LF splits) and "log bloat" (megabyte-long names).
 * The cap is 120 characters — enough for any real username.
 *
 * @param string $value Raw value.
 * @return string Sanitized value.
 */
function usersauth_sanitizeForLog(string $value): string
{
    $value = preg_replace('/[\r\n\t\x00-\x1F]+/u', ' ', $value) ?? '';

    return mb_substr(trim($value), 0, 120, 'UTF-8');
}

/**
 * Kill the current Cotonti session.
 *
 * Used when the original administrator cannot be restored (banned, deleted,
 * inactive) so that the operator is not stuck inside the target account.
 *
 * The function unset's the Cotonti session key (`Cot::$sys['site_id']`) and
 * expires the corresponding cookie. The next request will be treated as a
 * guest and the administrator can log in again from scratch.
 *
 * @return void
 */
function usersauth_clearSession(): void
{
    $siteId = (string) (Cot::$sys['site_id'] ?? '');
    if ($siteId === '') {
        return;
    }

    unset($_SESSION[$siteId]);
    cot_setcookie($siteId, '', time() - 3600);
}

/**
 * Perform the "login as" action.
 *
 * Full lifecycle:
 *   1. CSRF check.
 *   2. Permission check.
 *   3. Target existence check.
 *   4. Guard against logging in as yourself without an active anchor.
 *   5. Guard against non-superadmin logging in as superadmin.
 *   6. Save the original administrator ("anchor") once per session.
 *   7. Snapshot the target user's visit / token fields.
 *   8. `cot_user_authorize($user, true)` — full Cotonti authorization.
 *   9. When `$countVisit === false`, restore the visit / token fields.
 *  10. Write an entry to `cot_log`.
 *
 * @param int       $targetUserId Target user ID.
 * @param bool|null $countVisit   `null` reads the `count_visit` setting;
 *                                `true` counts the login as a real visit;
 *                                `false` restores the snapshot after auth.
 * @return array{error?: string, success?: true, user?: array}
 */
function usersauth_loginAs(int $targetUserId, ?bool $countVisit = null): array
{
    if (!usersauth_verifyCsrf()) {
        return ['error' => 'csrf'];
    }

    if (!usersauth_isAllowed()) {
        return ['error' => 'forbidden'];
    }

    if ($targetUserId < 1) {
        return ['error' => 'not_found'];
    }

    if ($countVisit === null) {
        $countVisit = !empty(Cot::$cfg['plugin']['usersauth']['count_visit']);
    }

    // Logging in as yourself without an active anchor makes no sense and would
    // destroy the ability to return (there is nowhere to return to).
    if (
        $targetUserId === (int) (Cot::$usr['id'] ?? 0)
        && empty($_SESSION['usersauth_original'])
    ) {
        return ['error' => 'self'];
    }

    $user = Cot::$db->query(
        'SELECT * FROM ' . Cot::$db->users . ' WHERE user_id = ? LIMIT 1',
        $targetUserId
    )->fetch();

    if (empty($user)) {
        return ['error' => 'not_found'];
    }

    $user['user_id']      = (int) $user['user_id'];
    $user['user_maingrp'] = (int) $user['user_maingrp'];

    cot_fillGroupsForUser($user);

    // Non-superadmin must not gain superadmin privileges via this tool.
    if (
        in_array(COT_GROUP_SUPERADMINS, $user['groups'] ?? [], true)
        && !in_array(COT_GROUP_SUPERADMINS, Cot::$usr['groups'] ?? [], true)
    ) {
        return ['error' => 'superadmin'];
    }

    // Save the original administrator exactly once — the first time we switch.
    // Subsequent switches update only `usersauth_target`, never the anchor.
    if (empty($_SESSION['usersauth_original'])) {
        $_SESSION['usersauth_original'] = [
            'id'         => (int) Cot::$usr['id'],
            'name'       => (string) Cot::$usr['name'],
            'maingrp'    => (int) Cot::$usr['maingrp'],
            'token'      => (string) (Cot::$sys['xk'] ?? ''),
            'started_at' => time(),
        ];
    }

    $_SESSION['usersauth_target'] = [
        'id'   => $user['user_id'],
        'name' => (string) $user['user_name'],
    ];

    // Snapshot visit / token fields so we can roll them back when `count_visit`
    // is off. `user_token` is included because `cot_user_authorize()` always
    // rotates it — without rollback, CSRF tokens on the user's open tabs break.
    $prevLastlog   = (int)    ($user['user_lastlog']   ?? 0);
    $prevLastvisit = (int)    ($user['user_lastvisit'] ?? 0);
    $prevLogcount  = (int)    ($user['user_logcount']  ?? 0);
    $prevLastip    = (string) ($user['user_lastip']    ?? '');
    $prevToken     = (string) ($user['user_token']     ?? '');

    $result = cot_user_authorize($user, true);

    if (!empty($result['error'])) {
        unset($_SESSION['usersauth_target']);
        return ['error' => 'auth_failed'];
    }

    if (!$countVisit) {
        Cot::$db->update(
            Cot::$db->users,
            [
                'user_lastlog'   => $prevLastlog,
                'user_lastvisit' => $prevLastvisit,
                'user_logcount'  => $prevLogcount,
                'user_lastip'    => $prevLastip,
                'user_token'     => $prevToken,
            ],
            'user_id = ?',
            [$user['user_id']]
        );
    }

    $adminName = usersauth_sanitizeForLog((string) ($_SESSION['usersauth_original']['name'] ?? 'admin'));
    $userName  = usersauth_sanitizeForLog((string) $user['user_name']);

    // Note: `cot_rc()` only expands `{...}` placeholders when the first argument
    // is a key inside the `$R` array. Since these strings live in `$L`, we use
    // `str_replace()` directly.
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

    return ['success' => true, 'user' => $user];
}

/**
 * Return to the original administrator account.
 *
 * Failure modes:
 *   - `csrf`       — XSS token missing or wrong.
 *   - `no_original`— no anchor in the session (never impersonated).
 *   - `not_found`  — original admin account deleted. Session is cleared so
 *                    the operator is not stuck.
 *   - `auth_failed`— `cot_user_authorize()` rejected the original (banned,
 *                    inactive). Session is cleared.
 *
 * @return array{error?: string, success?: true}
 */
function usersauth_return(): array
{
    if (!usersauth_verifyCsrf()) {
        return ['error' => 'csrf'];
    }

    if (empty($_SESSION['usersauth_original']['id'])) {
        return ['error' => 'no_original'];
    }

    $originalId = (int) $_SESSION['usersauth_original']['id'];
    $targetName = (string) ($_SESSION['usersauth_target']['name'] ?? '');

    $user = Cot::$db->query(
        'SELECT * FROM ' . Cot::$db->users . ' WHERE user_id = ? LIMIT 1',
        $originalId
    )->fetch();

    if (empty($user)) {
        usersauth_clearSession();
        unset($_SESSION['usersauth_original'], $_SESSION['usersauth_target']);
        return ['error' => 'not_found'];
    }

    $result = cot_user_authorize($user, true);

    unset($_SESSION['usersauth_original'], $_SESSION['usersauth_target']);

    if (!empty($result['error'])) {
        // Original admin cannot be re-authorized — force logout so the
        // operator is not locked inside the target account.
        usersauth_clearSession();
        return ['error' => 'auth_failed'];
    }

    cot_log(
        str_replace(
            '{USER}',
            usersauth_sanitizeForLog($targetName),
            Cot::$L['usersauth_log_return']
        ),
        'usersauth',
        'return',
        'done'
    );

    return ['success' => true];
}

/**
 * Build the admin login-as URL.
 *
 * @param int    $userId   Target user ID.
 * @param string $redirect Optional internal path to return to after login.
 *                         Encoded as base64 in the query string.
 * @return string
 */
function usersauth_url(int $userId, string $redirect = ''): string
{
    $params = [
        'm'   => 'other',
        'p'   => 'usersauth',
        'a'   => 'login',
        'uid' => $userId,
        'x'   => (string) (Cot::$sys['xk'] ?? ''),
    ];
    if ($redirect !== '') {
        $params['redirect'] = base64_encode($redirect);
    }

    return cot_url('admin', $params, '', true);
}

/**
 * Build the public (front-end) login-as URL.
 *
 * @param int $userId Target user ID.
 * @return string
 */
function usersauth_publicUrl(int $userId): string
{
    return cot_url('usersauth', [
        'a'   => 'login',
        'uid' => $userId,
        'x'   => (string) (Cot::$sys['xk'] ?? ''),
    ], '', true);
}

/**
 * Build the return URL.
 *
 * @return string
 */
function usersauth_returnUrl(): string
{
    return cot_url('usersauth', [
        'a' => 'return',
        'x' => (string) (Cot::$sys['xk'] ?? ''),
    ], '', true);
}

/**
 * Whether the current session is impersonating another user.
 *
 * @return bool
 */
function usersauth_isImpersonating(): bool
{
    return !empty($_SESSION['usersauth_original']['id'])
        && !empty($_SESSION['usersauth_target']['id']);
}

/**
 * Whether the impersonation lifetime has been exceeded.
 *
 * Uses `session_lifetime` from the plugin config. `0` (or negative) disables
 * the limit.
 *
 * @return bool
 */
function usersauth_isExpired(): bool
{
    if (empty($_SESSION['usersauth_original']['started_at'])) {
        return false;
    }

    $lifetime = (int) (Cot::$cfg['plugin']['usersauth']['session_lifetime'] ?? 3600);
    if ($lifetime <= 0) {
        return false;
    }

    return (time() - (int) $_SESSION['usersauth_original']['started_at']) > $lifetime;
}

/**
 * Validate and decode the post-login redirect target.
 *
 * Only internal paths (`/some/path`) are permitted. Absolute URLs,
 * protocol-relative URLs, backslash tricks and CRLF injections are rejected
 * and cause a fallback to `cot_url('index')`.
 *
 * @param string $redirectB64 base64-encoded redirect from the query string.
 * @return string Safe URL to redirect to.
 */
function usersauth_safeRedirect(string $redirectB64): string
{
    if ($redirectB64 === '') {
        return cot_url('index');
    }

    $decoded = base64_decode($redirectB64, true);
    if ($decoded === false || $decoded === '') {
        return cot_url('index');
    }

    // Only internal absolute paths are allowed.
    if (!str_starts_with($decoded, '/')) {
        return cot_url('index');
    }

    // Reject protocol-relative and backslash tricks.
    if (str_starts_with($decoded, '//') || str_starts_with($decoded, '/\\')) {
        return cot_url('index');
    }
    if (str_contains($decoded, '\\')) {
        return cot_url('index');
    }

    // Reject CR/LF (header injection).
    if (preg_match('/[\r\n]/', $decoded)) {
        return cot_url('index');
    }

    return $decoded;
}