<?php
## Using API...
require_once 'ScheduleAPI.php';
$api = new ScheduleAPI();

## Reading request...
$_GET = array_filter($_GET);
$_GET = array_map('trim', $_GET);
$method = $_GET['method'];
$term = isset($_GET['term']) ? $_GET['term'] : '';

## Processing request...
if ($method == 'getAuditoriums')
    die(json_encode($api->getAuditoriums($term)));
elseif ($method == 'getGroups')
    die(json_encode($api->getGroups($term)));
elseif ($method == 'getTeachers')
    die(json_encode($api->getTeachers($term)));
elseif ($method == 'getSchedule') {
    $events = $api->getSchedule($_GET['date_beg'], $_GET['date_end'],
        isset($_GET['id_grp']) ? $_GET['id_grp'] : NULL,
        isset($_GET['id_aud']) ? $_GET['id_aud'] : NULL,
        isset($_GET['id_fio']) ? $_GET['id_fio'] : NULL,
        false); // Cache?

    if (count($events) == 0)
        die('<div class="text-center">Вибачте, нічого не знайдено. Спробуйте використати інщі параметри запиту.</div>');

    $tpl = file_get_contents("template.html");
    preg_match("/(.*)({EVENTS:}(.*){ENDEVENTS})(.*)/s", $tpl, $matches);
    $group_start = $matches[1];
    $group_body = $matches[3];
    $group_end = $matches[4];

    ## Filling template
    $i = 0;
    foreach ($events as $date => $day) {
        echo str_replace('{EVENT_GROUP_TIME}', ua_date(ucfirst(strftime("%A, %d.%m.%Y", strtotime($date)))), $group_start);
        foreach ($day as $event) {
            $body = array(
                '{EVENT_HEADING}'   => $event['ABBR_DISC'] . ($event['NAME_STUD'] != '' ? chr(32) . '(' . $event['NAME_STUD'] . ')' : ''),
                '{EVENT_LECTURER}'  => $event['NAME_FIO'] != '' ? 'Викладач:' . chr(32) . $event['NAME_FIO'] : '',
                '{EVENT_TIME}'      => $event['TIME_PAIR'],
                '{EVENT_LOCATION}'  => $event['NAME_AUD'] != '' ? $event['NAME_AUD'] : '',
                '{EVENT_GROUP}'     => $event['NAME_GROUP'],
                '{ITEM_CLASS}'       => ''
            );
            if ($i == 0) {
                $evTimeEnd = explode('-', $event['TIME_PAIR']);
                $evTimeEnd = strtotime($evTimeEnd[1]);
                $evTimeStart = $evTimeEnd - 80 * 60; // -1:20 = 80 min;
                $currTime = time();

                if ($currTime >= $evTimeStart && $currTime <= $evTimeEnd)
                    $body['{ITEM_CLASS}'] = 'list-group-item-success';
                else if ($currTime > $evTimeEnd)
                    $body['{ITEM_CLASS}'] = 'list-group-item-passed';
            }
            echo str_replace(array_keys($body), $body, $group_body);
        }
        $i++;
        echo $group_end;
    }
    die();
} else {
    die("Unknown method");
}

function ua_date($text) {
    $translate = array(
        "am" => "am",
        "pm" => "pm",
        "AM" => "AM",
        "PM" => "PM",
        "Monday" => "Понеділок",
        "Mon" => "Пн",
        "Tuesday" => "Вівторок",
        "Tue" => "Вт",
        "Wednesday" => "Середа",
        "Wed" => "Ср",
        "Thursday" => "Четвер",
        "Thu" => "Чт",
        "Friday" => "П'ятниця",
        "Fri" => "Пт",
        "Saturday" => "Субота",
        "Sat" => "Сб",
        "Sunday" => "Неділя",
        "Sun" => "Нд",
        "January" => "Січня",
        "Jan" => "Січ",
        "February" => "Лютого",
        "Feb" => "Лют",
        "March" => "Березня",
        "Mar" => "Берез",
        "April" => "Квітня",
        "Apr" => "Квіт",
        "May" => "Травня",
        "June" => "Червня",
        "Jun" => "Черв",
        "July" => "Липня",
        "Jul" => "Лип",
        "August" => "Серпня",
        "Aug" => "Серп",
        "September" => "Вересня",
        "Sep" => "Верес",
        "October" => "Жовтня",
        "Oct" => "Жов",
        "November" => "Листопада",
        "Nov" => "Лис-топ",
        "December" => "Грудня",
        "Dec" => "Груд",
        "st" => "е",
        "nd" => "е",
        "rd" => "е",
        "th" => "е"
    );
    return strtr($text, $translate);
}
