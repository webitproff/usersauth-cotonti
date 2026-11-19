<?php
/* ====================
[BEGIN_COT_EXT]
Code=usersauth
Name=User Auth Vision
Category=users
Description=Allows super-administrators to securely log in as any user for testing (with optional visit counting and easy return)
Version=2.2.1
Date=2026-11-19
Author=webitproff
Copyright=Copyright (c) 2026 webitproff https://github.com/webitproff/usersauth-cotonti
Notes=Требования к окружению (PHP, MySQL, mbstring, Bootstrap 5 и Font Awesome)
Auth_guests=R
Lock_guests=W12345A
Auth_members=R
Lock_members=W12345A
Requires_modules=users
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
count_visit=01:radio::0:Count this login as a real visit (update lastlog / logcount / lastip)
allow_groups=03:text:::Group IDs allowed to use this tool (comma-separated, empty = superadmins only)
session_lifetime=04:string::3600:Impersonation session lifetime in seconds (0 = unlimited)
[END_COT_EXT_CONFIG]
==================== */

defined('COT_CODE') or die('Wrong URL');

/**
 * Setup & registration file for the "User Auth Vision" plugin.
 *
 * File: plugins/usersauth/usersauth.setup.php
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @copyright Copyright (c) 2026 webitproff | https://github.com/webitproff/usersauth-cotonti
 * @license BSD
 *
 * ═════════════════════════════════════════════════════════════════════
 *   НАЗНАЧЕНИЕ ФАЙЛА — ПАСПОРТ ПЛАГИНА
 * ═════════════════════════════════════════════════════════════════════
 *
 * `usersauth.setup.php` — единственная точка, через которую плагин
 * становится известен ядру Cotonti. Без этого файла плагин для системы
 * не существует: его нет в списке расширений, его невозможно
 * установить, метаданные недоступны.
 *
 * По роли это «паспорт» расширения. Всё, что система знает о плагине
 * до установки, берётся из блока `BEGIN_COT_EXT ... END_COT_EXT`
 * в начале файла. Всё, что система знает после установки, записано
 * в таблицах БД и было взято оттуда же при установке.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   МЕСТО В СИСТЕМЕ — ГДЕ И КЕМ ЧИТАЕТСЯ ФАЙЛ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Файл читается ядром Cotonti через `cot_infoget()` (system/extensions.php):
 * находит маркеры `BEGIN_COT_EXT` / `END_COT_EXT`, разбирает содержимое
 * как `Key = Value` и возвращает ассоциативный массив.
 *
 * Точки чтения:
 *
 *   1. admin.php?m=extensions
 *      Список расширений и страница «details». Ядро сканирует каталоги
 *      `plugins/` и `modules/`, для каждого подкаталога ищет файл
 *      `<code>/<code>.setup.php` и вызывает `cot_infoget()`.
 *
 *   2. cot_extension_install() — установка / обновление.
 *      При активации расширения ядро обязано прочитать метаданные:
 *      версию, имя, права, зависимости.
 *
 *   3. cot_extension_dependencies_statisfied() — проверка
 *      `Requires_modules` / `Requires_plugins` перед установкой.
 *
 *   4. ExtensionsHelper::getTitle() / getDescription()
 *      Если языковой файл не задаёт `usersauth_title` /
 *      `usersauth_description`, ядро берёт значения полей `Name` /
 *      `Description` из setup-файла как fallback.
 *
 *   5. admin.php?m=extensions&a=hooks — не читает setup напрямую,
 *      использует данные из `cot_plugins`, куда они попали при установке.
 *
 *   6. Удаление расширения (`cot_extension_uninstall`) — перечитывает
 *      setup-файл для проверки зависимостей других расширений.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ДЕЛАЕТ ФАЙЛ — ПОЛЯ И КУДА ЯДРО ЗАПИСЫВАЕТ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Формат блока — построчный `Key = Value`, значения могут содержать
 * пробелы и любые символы, кроме переводов строк.
 *
 *   Code            = usersauth
 *                     Уникальный код плагина. Совпадает с именем
 *                     каталога: `plugins/usersauth/` → `Code=usersauth`.
 *                     Записывается в:
 *                       cot_core.ct_code,
 *                       cot_plugins.pl_code,
 *                       cot_auth.auth_option (при auth_code='plug'),
 *                       cot_config.config_cat.
 *
 *   Name            = User Auth Vision
 *                     Отображаемое имя. → cot_core.ct_title,
 *                     cot_plugins.pl_title. Fallback в
 *                     ExtensionsHelper::getTitle().
 *
 *   Category        = users
 *                     Категория в списке расширений. В БД не пишется,
 *                     используется для сортировки/группировки при
 *                     выводе. Локализуется через `$L['ext_cat_<cat>']`.
 *
 *   Description     = Allows super-administrators to securely log in…
 *                     Краткое описание. В БД не пишется (в схеме
 *                     cot_plugins такого поля нет), читается на лету.
 *                     Fallback для ExtensionsHelper::getDescription().
 *
 *   Version         = 2.2.1
 *                     → cot_core.ct_version. По ней ядро сравнивает
 *                     текущую версию с БД (`version_compare`) и решает,
 *                     нужно ли обновление. При `$update=true` и
 *                     совпадении версий → COT_EXT_NOTHING_TO_UPDATE.
 *
 *   Date            = 2026-11-19
 *                     Дата выпуска. Только для отображения.
 *
 *   Author          = webitproff
 *   Copyright       = Copyright (c) 2026 webitproff
 *   Notes           = (пусто)
 *                     Только для отображения.
 *
 *   ─── Права ───
 *
 *   Auth_guests     = R
 *   Lock_guests     = W12345A
 *   Auth_members    = R
 *   Lock_members    = W12345A
 *
 *   При установке конвертируются через `cot_auth_getvalue()` и пишутся
 *   в `cot_auth.auth_rights` / `cot_auth.auth_rights_lock` для групп
 *   COT_GROUP_GUESTS, COT_GROUP_INACTIVE (guests-ветка) и остальных
 *   групп (members-ветка). Для плагина строки пишутся с
 *   `auth_code='plug'` и `auth_option='usersauth'`.
 *
 *   Специальные группы обрабатываются ядром отдельно:
 *     COT_GROUP_BANNED      → auth_rights=0,  auth_rights_lock=255
 *     COT_GROUP_SUPERADMINS → auth_rights=255, auth_rights_lock=255
 *
 *   ─── Зависимости ───
 *
 *   Requires_modules  = users
 *   Requires_plugins  = (поле отсутствует — зависимостей от плагинов нет)
 *
 *   Проверяется `cot_extension_dependencies_statisfied()` до установки.
 *   В БД не пишется.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   МАСКИ ПРАВ — ЧТО ЗНАЧАТ И ЗАЧЕМ ТАКИЕ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Auth_guests=R    — гостям выдаётся бит `R` (чтение). Без бита `R`
 *                      на `plug.usersauth` функция `cot_getextplugins()`
 *                      не подключает баннерный хук `header.user.tags`
 *                      для имперсонированного пользователя, и плашка
 *                      возврата не отрисуется.
 *   Lock_guests=W12345A — блокирует для гостей биты W/1..5/A, чтобы
 *                      редактор прав не мог случайно их выдать.
 *
 *   Auth_members=R   — зарегистрированным пользователям — `R`.
 *                      Обязательно, чтобы `header.user.tags` сработал
 *                      в сессии имперсонированного пользователя,
 *                      иначе администратор «застрянет» под чужой
 *                      учёткой без кнопки возврата.
 *   Lock_members=W12345A — блокирует W/1..5/A для обычных
 *                      пользователей. Право `A` на `plug.usersauth`
 *                      при этом всё равно может быть выдано явно
 *                      доверенной группе через «Админ → Права».
 *                      Ни одной группе `A` автоматически не выдаётся.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   КОНФИГУРАЦИЯ — КЛЮЧИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   count_visit       (radio, 0|1, по умолчанию 0)
 *                     Засчитывать вход как реальный визит: обновлять
 *                     `user_lastlog`, `user_logcount`, `user_lastip`.
 *                     При `0` плагин снимает и восстанавливает эти поля
 *                     и `user_token` после авторизации.
 *
 *   allow_groups      (text, по умолчанию пусто)
 *                     ID групп через запятую, которым разрешён
 *                     инструмент помимо суперадминистраторов.
 *                     Пусто = только суперадминистраторы.
 *                     Системные группы (0/1/2/3) в этом поле
 *                     игнорируются функцией `usersauth_isAllowed()`.
 *
 *   session_lifetime  (string, секунды, по умолчанию 3600)
 *                     Время жизни сессии имперсонации. После его
 *                     истечения плагин автоматически возвращает
 *                     администратора в его аккаунт. `0` — без
 *                     ограничения по времени.
 *
 *   Нумерация ключей в этом файле — `01`, `03`, `04` (ключ `02`
 *   отсутствует). Это состояние самого файла; в README таблица
 *   настроек тоже нумерует их как 01, 03, 04.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ПРОИСХОДИТ ПРИ УСТАНОВКЕ (по README, раздел 4.3)
 * ═════════════════════════════════════════════════════════════════════
 *
 *   1. Читается `usersauth.setup.php` (блок `BEGIN_COT_EXT`).
 *   2. Проверяется зависимость `Requires_modules=users`.
 *   3. Сканируется каталог `plugins/usersauth/`; каждый файл
 *      `usersauth.*.php`, кроме `setup`, `install`, `uninstall`,
 *      `configure`, читается как хук-файл, из блока `BEGIN_COT_EXT`
 *      берётся поле `Hooks`.
 *   4. В `cot_core` пишется строка: `ct_code='usersauth'`,
 *      `ct_version='2.2.1'`, `ct_plug=1`.
 *   5. В `cot_plugins` пишутся все хуки.
 *   6. В `cot_auth` пишутся права для групп согласно `Auth_guests`,
 *      `Lock_guests`, `Auth_members`, `Lock_members`.
 *   7. В `cot_config` пишутся дефолтные значения настроек:
 *      `count_visit='0'`, `allow_groups=''`, `session_lifetime='3600'`.
 *
 *   Каталог `setup/` отсутствует: SQL-скриптов и PHP-обработчиков
 *   установки у плагина нет. Плагин не создаёт собственных таблиц
 *   в БД — использует штатные `cot_users`, `cot_auth`, `cot_core`,
 *   `cot_config`, `cot_log`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ПРОИСХОДИТ ПРИ ОБНОВЛЕНИИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   По общим правилам
 *   ядра (см. system/extensions.php): сравнивается `Version` из
 *   setup-файла с `cot_core.ct_version`; при равенстве и выключенном
 *   `force_update` возвращается `COT_EXT_NOTHING_TO_UPDATE`. Иначе
 *   старые привязки хуков удаляются из `cot_plugins`, каталог
 *   сканируется заново, хуки перерегистрируются, обновляется
 *   `cot_core.ct_version`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ПРОИСХОДИТ ПРИ УДАЛЕНИИ 
 * ═════════════════════════════════════════════════════════════════════
 *
 *   При `cot_extension_uninstall`:
 *     - удаляются записи `cot_plugins` с `pl_code='usersauth'`;
 *     - удаляются записи `cot_auth` с `auth_code='plug'` и
 *       `auth_option='usersauth'`;
 *     - удаляются записи `cot_config` с `config_cat='usersauth'`;
 *     - удаляется запись `cot_core` с `ct_code='usersauth'`.
 *
 *   Файлы плагина автоматически не удаляются — их нужно стереть
 *   из `plugins/usersauth/` вручную. Таблицы `cot_users`, `cot_log`
 *   не затрагиваются.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПОЧЕМУ setup.php НЕ РЕГИСТРИРУЕТСЯ КАК ХУК
 * ═════════════════════════════════════════════════════════════════════
 *
 *   В system/extensions.php есть список пропускаемых суффиксов —
 *   `configure`, `install`, `setup`, `uninstall`. При сканировании
 *   каталога ядро пропускает файлы с этими суффиксами и не пытается
 *   регистрировать их как обработчики хуков. `usersauth.setup.php`
 *   — не хук-файл, а метаданные. Хук-файлы плагина имеют собственные
 *   маленькие блоки `BEGIN_COT_EXT` с полем `Hooks=...`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   РОЛЬ ПЛАГИНА В АРХИТЕКТУРЕ COTONTI
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Плагин (plug), а не модуль (module):
 *     - не объявляет собственную область структуры;
 *     - не создаёт собственных таблиц в БД;
 *     - подключается через хуки.
 *
 *   Задача плагина — дать суперадминистратору (и опционально
 *   пользователям разрешённых групп) полноценно авторизоваться под
 *   любой учётной записью через штатную `cot_user_authorize()`,
 *   с возможностью мгновенного возврата в исходный аккаунт.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ХУКИ ПЛАГИНА 
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Обязательные :
 *
 *     tools                → usersauth.tools.php
 *                            Админ-страница:
 *                            admin.php?m=other&p=usersauth
 *
 *     standalone           → usersauth.php
 *                            Публичный контроллер:
 *                            index.php?e=usersauth&a=login|return
 *
 *     users.details.tags   → usersauth.users.details.tags.php
 *                            Кнопка «Войти как» в профиле.
 *
 *     users.loop           → usersauth.users.loop.php
 *                            Кнопка «Войти как» в списке пользователей.
 *
 *   Баннерные :
 *
 *     header.user.tags     → usersauth.header.tags.php
 *                            Плашка в шапке сайта (вариант A).
 *
 *     header.first         → usersauth.header.first.php
 *                            Строка в notices Cotonti (вариант B).
 *
 *     footer.tags          → usersauth.footer.tags.php
 *                            Bootstrap 5 Toast (вариант C).
 *
 *   В тексте README сказано «пять обязательных хуков», но в таблице
 *   обязательных хуков перечислено четыре файла. Здесь перечислены
 *   те четыре, что указаны в таблице README и в разделе 17
 *   «Структура файлов». Пятого обязательного хук-файла в README
 *   не описано.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СОСТАВ ПЛАГИНА 
 * ═════════════════════════════════════════════════════════════════════
 *
 *   plugins/usersauth/
 *   ├── usersauth.setup.php                  ← этот файл (метаданные)
 *   ├── inc/
 *   │   └── usersauth.functions.php          ← ядро: функции usersauth_*
 *   ├── usersauth.php                        ← хук standalone
 *   ├── usersauth.tools.php                  ← хук tools
 *   ├── usersauth.header.tags.php            ← баннер, вариант A
 *   ├── usersauth.header.first.php           ← баннер, вариант B
 *   ├── usersauth.footer.tags.php            ← баннер, вариант C
 *   ├── usersauth.users.details.tags.php     ← хук users.details.tags
 *   ├── usersauth.users.loop.php             ← хук users.loop
 *   ├── lang/
 *   │   └── usersauth.ru.lang.php            ← русская локализация
 *   └── tpl/
 *       └── usersauth.admin.tpl              ← шаблон админ-страницы
 *
 *   Активным должен быть один из трёх баннерных файлов; остальные
 *   переименовываются в `.bak`, иначе Cotonti зарегистрирует все три
 *   хука и баннер появится в нескольких местах одновременно.
 *   а лучше просто воспользоваться кнопкой "Приостановить" напротив хука
 *   на странице плагина в админке
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПРАВА ДОСТУПА
 * ═════════════════════════════════════════════════════════════════════
 *
 *   После установки страница плагина доступна пользователям,
 *   удовлетворяющим хотя бы одному условию:
 *
 *     cot_auth('admin', 'a')                   — администратор сайта;
 *     cot_auth('plug', 'usersauth', 'A')       — право 'A' на плагин.
 *
 *   Дополнительно срабатывает `usersauth_isAllowed()` — суперадмин
 *   либо группа из `allow_groups`. 
 *   
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЗАВИСИМОСТИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Обязательная зависимость — модуль `users` (`Requires_modules=users`).
 *   Других обязательных или рекомендуемых зависимостей в setup-файле
 *   не объявлено. Требования к окружению (PHP, MySQL, mbstring,
 *   опционально Bootstrap 5 и Font Awesome) см. README, раздел 3.
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