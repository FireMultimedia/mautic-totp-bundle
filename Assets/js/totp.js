mQuery(document).ajaxStop(() => {
    if(window.location.pathname !== "/s/account")
        return;

    if(mQuery("#totp_setup_button").length > 0)
        return;

    location.reload();
});
