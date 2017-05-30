<?php

@setlocale(LC_ALL, "en_US.UTF-8");

class neteaseLyric
{
    public function __construct() { }
    const musicApi = 'http://music.163.com/api/search/pc';
    const lyricApi = 'http://music.163.com/api/song/lyric';

    private function getContent($param) {
        $getOption = array(
            CURLOPT_URL => sprintf(
                                '%s?%s',
                                self::lyricApi,
                                $param),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIE => 'appver=1.5.0.75771;',
            CURLOPT_REFERER => 'http://music.163.com/'
        );
        try {
            $ch = curl_init();
            curl_setopt_array($ch,$getOption);
            $file_contents = curl_exec($ch);
        } catch(Exception $e) {
            curl_close($ch);
            return '{"uncollected":true,"sgc":true,"sfy":false,"qfy":false,"code":200}';
        }
        curl_close($ch);
        return $file_contents;
    }

    private function postConetnt($param) {
        $postOption = array(
            CURLOPT_URL => self::musicApi,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIE => 'appver=1.5.0.75771;',
            CURLOPT_REFERER => 'http://music.163.com/'
        );
        try {
            $ch = curl_init();
            curl_setopt_array($ch,$postOption);
            $file_contents = curl_exec($ch);
        } catch(Exception $e) {
            curl_close($ch);
            return '{"result":{"songCount":0},"code":200}';
        }
        curl_close($ch);
        return $file_contents;
    }

    private function searchMusic($title, $artist) {
        $searchParam = sprintf(
            's=%s+%s&limit=30&offset=0&type=1&total=true',
            urlencode($title),
            urlencode($artist));
        $content = json_decode($this->postConetnt($searchParam), true);
        if($content['result']['songCount'] != 0) {
            $songs = $content['result']['songs'];
            $results = array();
            foreach ($songs as &$song) {
                $results[] = array(
                    'artist' => $song['artists'][0]['name'],
                    'title' => $song['name'],
                    'id' => $song['id']
                );
            }
            return $results;
        }
        return array();
    }

    public function getLyricsList($artist, $title, $info) {
        $list = $this->searchMusic($title, $artist);
        foreach ($list as &$lrc) {
            // if ($this->getLyrics($lrc['id'])) {
                $info->addTrackInfoToList($lrc['artist'], $lrc['title'], $lrc['id'], '');
            // } else {
                // unset($lrc);
            // }
        }
        return count($list);
    }

    public function getLyrics($id, $info = false) {
        $searchParam = sprintf(
            'id=%s&lv=-1&kv=-1&tv=-1',
            $id);
        $lyric = json_decode($this->getContent($searchParam), true);
        $_Llyric = array_key_exists('lrc', $lyric)
                    ? array_key_exists('lyric', $lyric['lrc'])
                        ? preg_split('/\n|\r\n?/', trim($lyric['lrc']['lyric']))
                        : array()
                    : array();
        $_Klyric = array_key_exists('klyric', $lyric)
                    ? array_key_exists('lyric', $lyric['klyric'])
                        ? preg_split('/\n|\r\n?/', trim($lyric['klyric']['lyric']))
                        : array()
                    : array();
        $_Tlyric = array_key_exists('tlyric', $lyric)
                    ? array_key_exists('lyric', $lyric['tlyric'])
                        ? preg_split('/\n|\r\n?/', trim($lyric['tlyric']['lyric']))
                        : array()
                    : array();
        $Llyric = array();
        $Klyric = array();
        $Tlyric = array();
        foreach ($_Llyric as $key => $value) {
            $kv = explode(']', $value);
            if ($kv) {
                $Llyric[$kv[0]] = $kv[1];
            }
        }
        foreach ($_Klyric as $key => $value) {
            $kv = explode(']', $value);
            if ($kv) {
                $Klyric[$kv[0]] = $kv[1];
            }
        }
        foreach ($_Tlyric as $key => $value) {
            $kv = explode(']', $value);
            if ($kv) {
                $Tlyric[$kv[0]] = $kv[1];
            }
        }
        foreach ($Llyric as $key => &$value) {
            $value = $key.']'.$value
                .(array_key_exists($key, $Klyric)
                    ? "\n".$this->addTime($key,1).']'.$Klyric[$key]
                    : '')
                .(array_key_exists($key, $Tlyric)
                    ? "\n".$this->addTime($key,2).']'.$Tlyric[$key]
                    : '');
        }
        $output = implode("\n", $Llyric);
        if ($info) {
            $info->addLyrics($output, $id);
        } else {
            if (strcmp($output, '')) {
                return false;
            }
            // return $output;
        }
        return true;
    }
    private function addTime($time, $offset) {
        $tmp = explode('.', $time);
        $tmp[1] = intval(array_key_exists(1, $tmp) ? $tmp[1] : 0) + $offset;
        return sprintf('%s.%02d', $tmp[0], $tmp[1]);
    }
}

?>