$(document).ready(function () {
    var scrollTop = 0;
    // Datapicker settings
    var datepickerOptions = {
        format: "dd.mm.yyyy",
        language: "ua",
        orientation: "top auto",
        todayHighlight: true,
        daysOfWeekDisabled: "0"
    };

    // Init datapicker
    $('.input-group.date').each(function () {
        var $textField = $(this).find('input[type="text"]');
        if ($(this).hasClass('next')) {
            datepickerOptions['startDate'] = '+7d';
            $textField.val(Service.getNextDatetime());
        } else {
            datepickerOptions['startDate'] = '0d';
            $textField.val(Service.getDatetime());
        }
        $(this).datepicker(datepickerOptions);
    });

    var $requestButton = $('button.btn-request');
    // Autocomplete common
    function fillAutocomplete($label, $value, data) {
        $label.val(data.item.label);
        $value.val(data.item.value);
    }

    // Autocomplete groups
    var $grText = $('div.input-group.groups input[type="text"]');
    var $grHidden = $('div.input-group.groups input[type="hidden"]');
    var $grLoading = $('div.input-group.groups .loading');
    var $grCleaner = $('div.input-group.groups a.clear-input');
    var $grPlaceholder = $('a.group-placeholder');
    $grText.on('change', function() {
        $grText.val('');
        $grHidden.val('');
        $grCleaner.hide();
    });
    $grText.autocomplete({
        source: 'php/index.php?method=getGroups',
        minLength: 1,
        search: function () {
            $grHidden.val('');
            $grLoading.fadeIn();
        },
        response: function () {
            $grLoading.promise().done(function () {
                    $grLoading.fadeOut();
                }
            );
        },
        select: function (event, ui) {
            fillAutocomplete($grText, $grHidden, ui);
            $grCleaner.show();
            $requestButton.focus();
            return false;
        }, focus: function (event, ui) {
            fillAutocomplete($grText, $grHidden, ui);
            return false;
        }
    });

    // Autocomplete to hidden fields
    $('.paramSwitch').click(function () {
        var $params = $('div.params');
        $params.toggle();
        if ($params.hasClass('loaded'))
            return true;
        $params.addClass('loaded');

        // Autocomplete teachers
        var $tchText = $('div.input-group.teacher input[type="text"]');
        var $tchHidden = $('div.input-group.teacher input[type="hidden"]');
        var $tchLoading = $('div.input-group.teacher .loading');
        var $tchCleaner = $('div.input-group.teacher a.clear-input');
        $tchText.on('change', function() {
            $tchText.val('');
            $tchHidden.val('');
            $grCleaner.hide();
        });
        $tchText.autocomplete({
            source: 'php/index.php?method=getTeachers',
            minLength: 2,
            search: function () {
                $tchHidden.val('');
                $tchLoading.fadeIn();
            },
            response: function () {
                $tchLoading.promise().done(function () {
                        $tchLoading.fadeOut()
                    }
                );
            },
            select: function (event, ui) {
                fillAutocomplete($tchText, $tchHidden, ui);
                $requestButton.focus();
                $tchCleaner.show();
                return false;
            }, focus: function (event, ui) {
                fillAutocomplete($tchText, $tchHidden, ui);
                return false;
            }
        });

        // Autocomplete rooms
        var $rmText = $('div.input-group.auditorium input[type="text"]');
        var $rmHidden = $('div.input-group.auditorium input[type="hidden"]');
        var $rmLoading = $('div.input-group.auditorium .loading');
        var $rmCleaner = $('div.input-group.auditorium a.clear-input');
        $rmText.on('change', function() {
            $rmText.val('');
            $rmHidden.val('');
            $grCleaner.hide();
        });
        $rmText.autocomplete({
            source: 'php/index.php?method=getAuditoriums',
            minLength: 1,
            search: function () {
                $rmLoading.fadeIn();
                $rmHidden.val('');
            },
            response: function () {
                $rmLoading.promise().done(function () {
                        $rmLoading.fadeOut()
                    }
                );
            },
            select: function (event, ui) {
                fillAutocomplete($rmText, $rmHidden, ui);
                $requestButton.focus();
                $rmCleaner.show();
                return false;
            }, focus: function (event, ui) {
                fillAutocomplete($rmText, $rmHidden, ui);
                return false;
            }
        });
    });

    // Making request
    $requestButton.click(function () {
        if (($grText.val() == '' || $grHidden.val() == '')
            && ($('div.auditorium input[type="text"]').val() == '' || $('div.auditorium input[type="hidden"]').val() == '')
            && ($('div.teacher input[type="text"]').val() == '' || $('div.teacher input[type="hidden"]').val() == '')) {
            Service.alert("<strong>Помилка!</strong> Хоча б одне поле <strong>Група/Аудиторія/Викладач</strong> повинно бути обрано з випадаючого списку.")
            return;
        }
        Service.showCogWheel();
        $.ajax({
            url: "php/index.php?method=getSchedule",
            timeout: 8000,
            type: "GET",
            data: $("form.requestForm").serialize()
        }).done(function (data) {
                $("div.form.schedule div.panel-body").html(data);
                $("div.form.schedule").show();
                scrollTop = $("div.form.schedule").offset().top - 20;
                $('html, body').scrollTop(scrollTop);
                if ($('input[name="remember"]').is(':checked')) {
                    Service.cookifyGroup($grHidden.val(), $grText.val());
                    $grPlaceholder.html($grText.val());
                    $grPlaceholder.show();
                }
            }).fail(function (jqXHR, textStatus) {
                Service.alert("<strong>Помилка!</strong> Сервер розкладу часто глючить, тому спробуйте повторно відіслати запит. Текст помилки: <strong>" + textStatus + "</strong>");
            }).always(function () {
                Service.hideCogWheel();
            });
    });

    $('footer').click(function() {
        $('html, body').scrollTop(scrollTop);
    });

    // Clear button
    var $clButtons = $('a.clear-input');
    $clButtons.on('click', function (e) {
        e.preventDefault();
        var $parent = $(this).closest('div.input-group');
        $(this).hide();
        $parent.find('input').val('');
    });

    // Loading data from cookies ...
    if (Service.isCookiefied()) {
        var $rem = $('input[name="remember"]');
        $rem.prop('checked', true);
        $grText.val(Service.getCookiefiedGroupName());
        $grHidden.val(Service.getCookiefiedGroupId());
        $requestButton.click();
        $grCleaner.show();
        $grPlaceholder.html(Service.getCookiefiedGroupName());
        $grPlaceholder.on('click', function(e) {
            e.preventDefault();
            $grText.val(Service.getCookiefiedGroupName());
            $grHidden.val(Service.getCookiefiedGroupId());
            $grCleaner.show();
        });
        $grPlaceholder.show();
        $rem.on('click', function() {
            $grPlaceholder.hide();
            Service.decookifyGroup();
        })
    }
});