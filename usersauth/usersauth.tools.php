<?php
declare(strict_types=1);
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 * User Auth Vision — admin tools interface.
 *
 * File: plugins/usersauth/usersauth.tools.php
 * Route: admin.php?m=other&p=usersauth
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
 * Административная страница плагина: поиск пользователей, кнопка
 * «войти под пользователем» и плашка «сейчас вы под …» с возвратом.
 * Регистрируется через хук `tools` — Cotonti подключает файл при
 * открытии `admin.php?m=other&p=usersauth`.
 *
 * Файл не создаёт таблиц, не пишет в БД сам — вся работа с БД
 * делегирована функциям из `inc/usersauth.functions.php`:
 * `usersauth_loginAs()`, `usersauth_return()`, `usersauth_isAllowed()`,
 * `usersauth_isImpersonating()`, `usersauth_isExpired()`,
 * `usersauth_returnUrl()`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   МЕСТО В ЦЕПОЧКЕ ЗАГРУЗКИ (по system/admin/admin.other.php)
 * ═════════════════════════════════════════════════════════════════════
 *
 *   admin.php
 *     → system/admin/admin.other.php при $p = 'usersauth'
 *       → Router::routeAdminOther('usersauth')
 *            → cot_getextplugins('tools', true, 'usersauth', 'plug')
 *            → Route::includeFiles = [plugins/usersauth/usersauth.tools.php]
 *       → list(...) = cot_auth('plug', 'usersauth'); cot_block(isadmin)
 *       → require_once cot_langfile('usersauth', 'plug') (при наличии)
 *       → $adminTitle = cot_get_extensionparams('usersauth', false)['name']
 *       → foreach ($route->includeFiles as $includeFile) {
 *             $pluginBody = '';
 *             include_once $includeFile;
 *             $adminMain .= $pluginBody;
 *         }
 *     → admin.main.php: $t->assign('ADMIN_MAIN', $adminMain), вывод
 *
 * Важно: `$adminTitle` и `$adminPath` (хлебные крошки) задаются в
 * `admin.other.php` до `include_once`. Сам `usersauth.tools.php` их
 * НЕ присваивает и НЕ переопределяет.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЗАВИСИМОСТИ (require_once)
 * ═════════════════════════════════════════════════════════════════════
 *
 *   - cot_incfile('usersauth', 'plug')   — inc/usersauth.functions.php;
 *   - cot_langfile('usersauth', 'plug')  — языковые строки;
 *   - cot_incfile('forms')               — cot_inputbox() и др.
 *
 * Защита от прямого вызова — `defined('COT_CODE') && defined('COT_ADMIN')
 * or die('Wrong URL');`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПРОВЕРКИ ДОСТУПА
 * ═════════════════════════════════════════════════════════════════════
 *
 * Две независимые проверки, обе должны пройти:
 *
 *   1. `list($plugRead, $plugWrite, $plugAdmin) = cot_auth('plug', 'usersauth');`
 *      `cot_block($plugAdmin);`
 *      Результат `cot_auth` кладётся в ЛОКАЛЬНЫЕ переменные.
 *      `Cot::$usr['isadmin']` намеренно не перезаписывается —
 *      перезапись сломала бы глобальный суперадмин-флаг для
 *      остальных плагинов и шаблонов в этом же запросе.
 *
 *   2. `usersauth_isAllowed()` — если `false`, то `cot_die_message(403, true)`.
 *      (Суперадмин либо группа из `allow_groups`.)
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ВХОДНЫЕ ПАРАМЕТРЫ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   $a   = cot_import('a',   'G', 'ALP');   // действие
 *   $uid = cot_import('uid', 'G', 'INT');   // целевой пользователь
 *   $sq  = trim(cot_import('sq', 'G', 'TXT', 64));  // поисковый запрос,
 *                                                   // ограничение 64 символа
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СЦЕНАРИИ ВЫПОЛНЕНИЯ (по порядку в коде)
 * ═════════════════════════════════════════════════════════════════════
 *
 *   1. АВТОВОЗВРАТ ПО ИСТЕЧЕНИИ СЕССИИ
 *      Условие: `usersauth_isImpersonating() && usersauth_isExpired()`.
 *      Действие:
 *        usersauth_return();
 *        cot_message($L['usersauth_error_session_expired'] ?? 'Session expired');
 *        cot_redirect(cot_url('admin', ['m'=>'other','p'=>'usersauth']));
 *      `cot_redirect()` завершает выполнение; следующая страница
 *      покажет сообщение из очереди Cotonti.
 *
 *   2. ПРЯМОЙ ЛОГИН (`a=login` и `$uid > 0`)
 *      Сначала `cot_check_xg()` — проверка GET-токена Cotonti.
 *      Затем `usersauth_loginAs($uid)` — внутри функции дополнительно
 *      выполняется `usersauth_verifyCsrf()` (двойная защита).
 *      - При ошибке: `$key = 'usersauth_error_' . $result['error'];`
 *        `cot_error($L[$key] ?? $L['Error']);` и редирект обратно на
 *        `admin.php?m=other&p=usersauth`.
 *      - При успехе: имя пользователя экранируется
 *        `htmlspecialchars((string) $result['user']['user_name'], ENT_QUOTES, 'UTF-8')`,
 *        подставляется через `str_replace('{NAME}', $userName, $L['usersauth_success'])`,
 *        кладётся в `cot_message(...)`, затем редирект на `cot_url('index')`.
 *      Порядок «сначала cot_check_xg(), потом loginAs» — оба шага
 *      выполняются, ни один не заменяет другой.
 *
 *   3. ПОИСК (когда `$sq !== ''`)
 *      SQL:
 *        SELECT user_id, user_name, user_email, user_maingrp,
 *               user_firstname, user_lastname
 *          FROM <users>
 *         WHERE LOCATE(:name, user_name) > 0
 *            OR LOCATE(:name, user_email) > 0
 *            OR CAST(user_id AS CHAR) = :exact
 *         ORDER BY user_name ASC
 *         LIMIT 50
 *      Параметры: `:name` = `$sq`, `:exact` = `$sq`.
 *      `LOCATE()` вместо `LIKE` — защита от LIKE-инъекции (`%`/`_`)
 *      и от LIKE-DoS на патологических шаблонах.
 *      Лимит результатов — 50.
 *
 *   4. ПЛАШКА ИМПЕРСОНАЦИИ (когда `usersauth_isImpersonating()`)
 *      Имя целевого пользователя читается напрямую из
 *      `$_SESSION['usersauth_target']['name']` (без запроса в БД)
 *      и экранируется `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
 *
 *   5. ФИНАЛ
 *      `$t->parse('MAIN');`
 *      `$adminMain = $t->text('MAIN');`
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ШАБЛОН И ПЕРЕДАЧА ДАННЫХ В НЕГО
 * ═════════════════════════════════════════════════════════════════════
 *
 * Шаблон: `cot_tplfile('usersauth.admin', 'plug')` — файл
 * `tpl/usersauth.admin.tpl` из поставки плагина (или переопределение
 * в теме плагина — по стандартному правилу `cot_tplfile`).
 *
 * Присваиваемые теги (полный список по коду):
 *
 *   Базовые:
 *     USERSAUTH_TITLE         = $L['usersauth_title']
 *     USERSAUTH_HELP          = $L['usersauth_help']
 *     USERSAUTH_FORM_ACTION   = cot_url('admin', ['m'=>'other','p'=>'usersauth'])
 *     USERSAUTH_SEARCH        = cot_inputbox('text', 'sq', $sq, [
 *                                 'placeholder' => $L['usersauth_search_placeholder'],
 *                                 'class'       => 'form-control',
 *                                 'style'       => 'max-width:320px',
 *                               ])
 *     USERSAUTH_SUBMIT        = cot_inputbox('submit', 'submit', $L['Search'])
 *
 *   Строки результатов (блок MAIN.SEARCH_RESULTS.ROW):
 *     USERSAUTH_ROW_ID        = (int) $u['user_id']
 *     USERSAUTH_ROW_NAME      = htmlspecialchars(<user_name> [(<First Last>)],
 *                                 ENT_QUOTES, 'UTF-8')
 *     USERSAUTH_ROW_EMAIL     = htmlspecialchars(<user_email>, ENT_QUOTES, 'UTF-8')
 *     USERSAUTH_ROW_GROUP     = cot_build_group((int) $u['user_maingrp'])
 *     USERSAUTH_ROW_URL       = cot_url('admin', ['m'=>'other','p'=>'usersauth',
 *                                 'a'=>'login','uid'=><id>,
 *                                 'x'=>Cot::$sys['xk']])
 *     USERSAUTH_ROW_PROFILE   = cot_url('users', ['m'=>'details','id'=><id>,
 *                                 'u'=><user_name>])
 *
 *   Блок «нет результатов» (MAIN.NO_RESULTS):
 *     USERSAUTH_NO_RESULTS    = $L['usersauth_error_not_found']
 *     (парсится только если `$sq !== ''` и `$users` пуст.)
 *
 *   Плашка имперсонации (блок MAIN.IMPERSONATING):
 *     USERSAUTH_CURRENT       = str_replace('{NAME}', $targetName,
 *                                 $L['usersauth_current'])
 *     USERSAUTH_RETURN_URL    = usersauth_returnUrl()
 *     USERSAUTH_RETURN_LINK   = cot_rc_link(usersauth_returnUrl(),
 *                                 $L['usersauth_return'])
 *
 *   Блоки, которые парсятся:
 *     MAIN.SEARCH_RESULTS.ROW  — на каждого найденного пользователя;
 *     MAIN.SEARCH_RESULTS      — после цикла, если `$users` непуст;
 *     MAIN.NO_RESULTS          — если запрос был, а результатов нет;
 *     MAIN.IMPERSONATING       — если имперсонация активна;
 *     MAIN                     — в самом конце, перед `$t->text('MAIN')`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ФОРМА ВЫВОДА — $adminMain (не $pluginBody)
 * ═════════════════════════════════════════════════════════════════════
 *
 * Файл заканчивается строкой:
 *
 *     $adminMain = $t->text('MAIN');
 *
 * То есть результат присваивается `$adminMain`, а не `$pluginBody`.
 * Это отличается от конвенции, которую использует, например,
 * `plugins/dragstructure/dragstructure.tools.php` (там —
 * `$pluginBody = $t->text('MAIN')`).
 *
 * `system/admin/admin.other.php` в цикле по `$route->includeFiles`
 * делает:
 *
 *     $pluginBody = '';
 *     include_once $includeFile;
 *     $adminMain .= $pluginBody;
 *
 * Так как `include_once` выполняется в той же области видимости,
 * `usersauth.tools.php` напрямую перезаписывает `$adminMain`, после
 * чего `$adminMain .= ''` в `admin.other.php` — no-op. Итог: HTML
 * от плагина попадает в `$adminMain` и далее в `ADMIN_MAIN`.
 *
 * Практическое следствие (факт, не оценка):
 *   - если на одном и том же `$p` будет несколько include-файлов
 *     (несколько плагинов на хуке tools), побеждает `$adminMain`
 *     последнего включённого файла — каждая перезапись затирает
 *     результат предыдущего;
 *   - конвенция `$pluginBody` позволяла бы аддитивно накапливать
 *     результат; текущая запись `$adminMain` — не аддитивна.
 *
 * читается ровно `$pluginBody`. `usersauth.tools.php`
 * 
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЛОГИРОВАНИЕ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Сам файл в лог не пишет. Все записи в `cot_log` выполняются из
 * `usersauth_loginAs()` и `usersauth_return()` (см. inc/usersauth.functions.php).
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПОВЕДЕНЧЕСКИЕ ОСОБЕННОСТИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   - Автовозврат при истечении `session_lifetime` выполняется только
 *     на этой странице (и в баннерных хуках `header.first` /
 *     `header.user.tags`); в `footer.tags` — нет (по коду хука, не по
 *     этому файлу).
 *   - Поиск выполняется всегда, когда `$sq !== ''`, даже если
 *     имперсонация активна — плашка и форма живут независимо.
 *   - Сообщение об успехе/ошибке ставится в очередь Cotonti ДО
 *     `cot_redirect()`, поэтому отображается на следующей странице.
 *   - `cot_check_xg()` вызывается в этом файле перед `loginAs()`,
 *     а внутри `usersauth_loginAs()` дополнительно выполняется
 *     `usersauth_verifyCsrf()`. Обе проверки — по `Cot::$sys['xk']`.
 *
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

defined('COT_CODE') && defined('COT_ADMIN') or die('Wrong URL');

require_once cot_incfile('usersauth', 'plug');
require_once cot_langfile('usersauth', 'plug');
require_once cot_incfile('forms');

// Local variables — `Cot::$usr['isadmin']` is intentionally NOT overwritten.
// Writing to `$usr['isadmin']` here would corrupt the global superadmin flag
// for the rest of the request (other plugins and templates check it).
list($plugRead, $plugWrite, $plugAdmin) = cot_auth('plug', 'usersauth');
cot_block($plugAdmin);

// Additional check: the tool is only available to superadmins and to groups
// explicitly listed in `allow_groups`.
if (!usersauth_isAllowed()) {
    cot_die_message(403, true);
}

$t = new XTemplate(cot_tplfile('usersauth.admin', 'plug'));

$a   = (string) cot_import('a', 'G', 'ALP');
$uid = (int) cot_import('uid', 'G', 'INT');

// Auto-return when the impersonation session has expired.
if (usersauth_isImpersonating() && usersauth_isExpired()) {
    usersauth_return();
    cot_message($L['usersauth_error_session_expired'] ?? 'Session expired');
    cot_redirect(cot_url('admin', ['m' => 'other', 'p' => 'usersauth']));
}

// Direct login triggered by a button in the admin table.
if ($a === 'login' && $uid > 0) {
    cot_check_xg();

    $result = usersauth_loginAs($uid);

    if (!empty($result['error'])) {
        $key = 'usersauth_error_' . $result['error'];
        cot_error($L[$key] ?? $L['Error']);
        cot_redirect(cot_url('admin', ['m' => 'other', 'p' => 'usersauth']));
    }

    $userName = htmlspecialchars((string) $result['user']['user_name'], ENT_QUOTES, 'UTF-8');
    cot_message(str_replace('{NAME}', $userName, $L['usersauth_success']));
    cot_redirect(cot_url('index'));
}

// Search.
$sq    = trim((string) cot_import('sq', 'G', 'TXT', 64));
$users = [];

if ($sq !== '') {
    // `LOCATE()` is used instead of `LIKE`. This avoids two problems:
    //   1. LIKE-wildcard injection — the user could otherwise pass `%` / `_`
    //      and turn the search into a "match everything" query.
    //   2. LIKE-based DoS — pathological patterns such as `%%%...%%%` on a
    //      large table.
    $sql = Cot::$db->query(
        'SELECT user_id, user_name, user_email, user_maingrp, user_firstname, user_lastname'
        . ' FROM ' . Cot::$db->users
        . ' WHERE LOCATE(:name, user_name) > 0'
        . '    OR LOCATE(:name, user_email) > 0'
        . '    OR CAST(user_id AS CHAR) = :exact'
        . ' ORDER BY user_name ASC LIMIT 50',
        [
            'name'  => $sq,
            'exact' => $sq,
        ]
    );
    $users = $sql->fetchAll();
}

$t->assign([
    'USERSAUTH_TITLE'       => $L['usersauth_title'],
    'USERSAUTH_HELP'        => $L['usersauth_help'],
    'USERSAUTH_FORM_ACTION' => cot_url('admin', ['m' => 'other', 'p' => 'usersauth']),
    'USERSAUTH_SEARCH'      => cot_inputbox('text', 'sq', $sq, [
        'placeholder' => $L['usersauth_search_placeholder'],
        'class'       => 'form-control',
        'style'       => 'max-width:320px',
    ]),
    'USERSAUTH_SUBMIT'      => cot_inputbox('submit', 'submit', $L['Search']),
]);

if (!empty($users)) {
    foreach ($users as $u) {
        $fullName = trim(((string) ($u['user_firstname'] ?? '')) . ' ' . ((string) ($u['user_lastname'] ?? '')));
        $display  = (string) $u['user_name'];
        if ($fullName !== '') {
            $display .= ' (' . $fullName . ')';
        }

        $loginUrl = cot_url('admin', [
            'm'   => 'other',
            'p'   => 'usersauth',
            'a'   => 'login',
            'uid' => (int) $u['user_id'],
            'x'   => (string) (Cot::$sys['xk'] ?? ''),
        ]);

        $t->assign([
            'USERSAUTH_ROW_ID'      => (int) $u['user_id'],
            'USERSAUTH_ROW_NAME'    => htmlspecialchars($display, ENT_QUOTES, 'UTF-8'),
            'USERSAUTH_ROW_EMAIL'   => htmlspecialchars((string) ($u['user_email'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'USERSAUTH_ROW_GROUP'   => cot_build_group((int) $u['user_maingrp']),
            'USERSAUTH_ROW_URL'     => $loginUrl,
            'USERSAUTH_ROW_PROFILE' => cot_url('users', [
                'm'  => 'details',
                'id' => (int) $u['user_id'],
                'u'  => (string) $u['user_name'],
            ]),
        ]);
        $t->parse('MAIN.SEARCH_RESULTS.ROW');
    }
    $t->parse('MAIN.SEARCH_RESULTS');
} elseif ($sq !== '') {
    $t->assign('USERSAUTH_NO_RESULTS', $L['usersauth_error_not_found']);
    $t->parse('MAIN.NO_RESULTS');
}

// Return banner is only rendered when impersonation is active.
if (usersauth_isImpersonating()) {
    $targetName = htmlspecialchars((string) ($_SESSION['usersauth_target']['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $t->assign([
        'USERSAUTH_CURRENT'     => str_replace('{NAME}', $targetName, $L['usersauth_current']),
        'USERSAUTH_RETURN_URL'  => usersauth_returnUrl(),
        'USERSAUTH_RETURN_LINK' => cot_rc_link(usersauth_returnUrl(), $L['usersauth_return']),
    ]);
    $t->parse('MAIN.IMPERSONATING');
}

$t->parse('MAIN');
$adminMain = $t->text('MAIN');