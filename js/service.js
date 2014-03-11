var Service;
(function ($) {
    Service = new
        (function () {
            var cookiefied;
            var id_grp;
            var name_grp;

            this.isCookiefied = function () {
                return cookiefied;
            };

            var setCookie = function (key, value) {
                var expires = new Date();
                expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
                document.cookie = key + '=' + value + ';expires=' + expires.toUTCString();
            };

            var getCookie = function (key) {
                var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
                return keyValue ? JSON.parse(keyValue[2]) : null;
            };

            var deleteCookie = function(key) {
                return document.cookie = key + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            };

            this.cookifyGroup = function (group_id, group_name) {
                if (group_id.length == 0 || group_name.length == 0) {
                    deleteCookie("_id_grp");
                    deleteCookie("_name_grp");
                    deleteCookie("_ISCOOKIEFIED");
                    console.log("Bad request cookies!")
                    return;
                }
                setCookie("_id_grp", group_id);
                setCookie("_name_grp", '"' + group_name + '"');
                setCookie("_ISCOOKIEFIED", true);
                id_grp = group_id;
                name_grp = group_name;
                cookiefied = true;
            };

            this.getCookiefiedGroupId = function () {
                return id_grp;
            };

            this.getCookiefiedGroupName = function () {
                return name_grp;
            };

            this.getDatetime = function () {
                var d = new Date();
                return ('0' + d.getDate()).slice(-2) + '.'
                    + ('0' + (d.getMonth() + 1)).slice(-2) + '.'
                    + d.getFullYear();
            };

            this.getNextDatetime = function () {
                var d = new Date();
                d.setDate(d.getDate() + 7);
                return ('0' + d.getDate()).slice(-2) + '.'
                    + ('0' + (d.getMonth() + 1)).slice(-2) + '.'
                    + d.getFullYear();
            };

            this.alert = function (s) {
                $("div.alert").html(s).show();
                $('html, body').scrollTop($("div.alert").offset().top - 20);

                setTimeout(function () {
                    $("div.alert").fadeOut();
                }, 5500)
            };

            this.showCogWheel = function () {
                $('#cogwheel').stop().modal('show');
            };

            this.hideCogWheel = function () {
                window.setTimeout(function () {
                    $('#cogwheel').stop().modal('hide');
                }, 1000);
            };

            (function () {
                if (cookiefied = getCookie("_ISCOOKIEFIED") == true) {
                    id_grp = getCookie("_id_grp");
                    name_grp = getCookie("_name_grp");
                } else {
                    cookiefied = id_grp = 0
                }
            })();
        });
})(jQuery);