<?php

class ScheduleAPI {
    private $auditoriumsURL = "http://schedule.sumdu.edu.ua/index/json?method=getAuditoriums";
    private $teachersURL = "http://schedule.sumdu.edu.ua/index/json?method=getTeachers";
    private $groupsURL = "http://schedule.sumdu.edu.ua/index/json?method=getGroups";
    private $scheduleURL = "http://schedule.sumdu.edu.ua/index/json?method=getSchedule";

    private $tempFolder = "temp/";

    private function getContents($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        $result = $this->toUTF($result);
        return json_decode($result, true);
    }

    private function toUTF($data) {
        return iconv(mb_detect_encoding($data, mb_detect_order(), true), "UTF-8", $data);
    }

    private function cache($data, $tag, $life = 86400) {
        $d = array('data' => $data,
            'meta' => array(
                'created' => time(),
                'life' => $life
            ));
        $data = serialize($d);
        $f = fopen($this->tempFolder . $tag . '.cache', "w+");
        fwrite($f, $data);
        fclose($f);
    }

    private function load($tag) {
        $f = $this->tempFolder . $tag . '.cache';
        if (!is_file($f))
            return false;
        $data = unserialize(file_get_contents($f));
        if (!isset($data['meta']) || (time() - $data['meta']['created'] > $data['meta']['life'] && $data['meta']['life'] != 0)) {
            unlink($f);
            return false;
        }
        return $data['data'];
    }

    public function setTempPath($p) {
        if (is_string($p) && is_dir($p)) {
            $this->tempFolder = $p;
            return true;
        }
        return false;
    }

    public function getAuditoriums($word = NULL) {
        $data = $this->load('auditoriums');
        if (!$data) {
            $aud7s = $this->getContents($this->auditoriumsURL);

            $data = array();
            foreach ($aud7s as $id => $name) {
                $data[] = array("value" => $id, "label" => $name);
            }

            $this->cache($data, 'auditoriums', 0);
        }

        if (!$word)
            return $data;

        $word = mb_ereg_replace("и", "і", mb_strtolower($this->toUTF($word), 'UTF-8'));
        $r = array();

        foreach ($data as $auditorium) {
            if (strpos(mb_strtolower($auditorium['label'], 'UTF-8'), $word) === 0)
                $r[] = $auditorium;
        }

        return $r;
    }

    public function getTeachers($word = NULL) {
        $data = $this->load('teachers');
        if (!$data) {
            $teachers = $this->getContents($this->teachersURL);

            $data = array();
            foreach ($teachers as $id => $name) {
                $data[] = array("value" => $id, "label" => $name);
            }

            $this->cache($data, 'teachers', 0);
        }

        if (!$word)
            return $data;

        $word = mb_strtolower($this->toUTF($word), 'UTF-8');
        $r = array();

        foreach ($data as $teacher) {
            if (strpos(mb_strtolower($teacher['label'], 'UTF-8'), $word) === 0)
                $r[] = $teacher;
        }

        return $r;
    }

    public function getGroups($word = NULL) {
        $data = $this->load('groups');
        if (!$data) {
            $groups = $this->getContents($this->groupsURL);

            $data = array();
            foreach ($groups as $id => $name) {
                $data[] = array("value" => $id, "label" => $name);
            }

            $this->cache($data, 'groups', 0);
        }

        if (!$word)
            return $data;

        $word =  mb_ereg_replace("и","і", mb_strtolower($this->toUTF($word), 'UTF-8'));
        $r = array();

        foreach ($data as $group) {
            if (strpos(mb_strtolower($group['label'], 'UTF-8'), $word) === 0)
                $r[] = $group;
        }

        return $r;
    }

    public function getSchedule($dateStart, $dateEnd, $groupId = NULL, $aud6mId = NULL, $teacherId = NULL, $cache = true) {
        $tag = 'schedule-' . md5($dateStart . $dateEnd . $groupId . $aud6mId . $teacherId);

        $cache === true ? $data = $this->load($tag) : $data = false;

        $url = $this->scheduleURL . '&date_beg=' . $dateStart . '&date_end=' . $dateEnd;
        if ($groupId)
            $url .= '&id_grp=' . $groupId;
        if ($aud6mId)
            $url .= '&id_aud=' . $aud6mId;
        if ($teacherId)
            $url .= '&id_fio=' . $teacherId;

        if (!$data) {
            $sch4e = $this->getContents($url);

            $data = array();
            foreach ($sch4e as $event)
                $data[$event["DATE_REG"]][] = $event;

            foreach ($data as $d => $ev)
                usort($data[$d], function ($a, $b) {
                    $aa = substr($a['TIME_PAIR'], 0, 2);
                    $bb = substr($b['TIME_PAIR'], 0, 2);
                    return $aa - $bb;
                });
            if ($cache === true)
                $this->cache($data, $tag);
        }

        return $data;
    }
}