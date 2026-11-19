<!--
/**
 * User Auth Vision — admin page template.
 *
 * @package usersauth
 * @version 2.2.1
 * @author  webitproff
 * @license BSD
 *
 * Available blocks:
 *   MAIN                         — page container
 *   MAIN.IMPERSONATING           — return banner (only while impersonating)
 *   MAIN.SEARCH_RESULTS          — search results table
 *   MAIN.SEARCH_RESULTS.ROW      — one result row
 *   MAIN.NO_RESULTS              — "nothing found" message
 */
-->
<!-- BEGIN: MAIN -->
<div class="block">
    <h2>{USERSAUTH_TITLE}</h2>
    <p class="text-muted">{USERSAUTH_HELP}</p>

    <!-- BEGIN: IMPERSONATING -->
    <div class="alert alert-warning">
        {USERSAUTH_CURRENT}
        &nbsp; {USERSAUTH_RETURN_LINK}
    </div>
    <!-- END: IMPERSONATING -->

    <form action="{USERSAUTH_FORM_ACTION}" method="get" class="form-inline mb-3">
        <input type="hidden" name="m" value="other" />
        <input type="hidden" name="p" value="usersauth" />
        {USERSAUTH_SEARCH}
        {USERSAUTH_SUBMIT}
    </form>

    <!-- BEGIN: SEARCH_RESULTS -->
    <table class="cells table table-striped">
        <tr>
            <th>ID</th>
            <th>{PHP.L.Username}</th>
            <th>{PHP.L.Email}</th>
            <th>{PHP.L.Group}</th>
            <th></th>
        </tr>
        <!-- BEGIN: ROW -->
        <tr>
            <td>{USERSAUTH_ROW_ID}</td>
            <td>
                <a href="{USERSAUTH_ROW_PROFILE}" target="_blank" rel="noopener noreferrer">{USERSAUTH_ROW_NAME}</a>
            </td>
            <td>{USERSAUTH_ROW_EMAIL}</td>
            <td>{USERSAUTH_ROW_GROUP}</td>
            <td>
                <a class="btn btn-sm btn-danger" href="{USERSAUTH_ROW_URL}"
                   target="_blank" rel="noopener noreferrer"
                   title="{PHP.L.usersauth_btn_open_new}">
                    {PHP.L.usersauth_btn_login}
                </a>
            </td>
        </tr>
        <!-- END: ROW -->
    </table>
    <!-- END: SEARCH_RESULTS -->

    <!-- BEGIN: NO_RESULTS -->
    <p class="text-muted">{USERSAUTH_NO_RESULTS}</p>
    <!-- END: NO_RESULTS -->
</div>
<!-- END: MAIN -->