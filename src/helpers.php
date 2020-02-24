<?php

if(!function_exists('app')) {
    /**
     * @return \OffeneVergaben\Console\Application
     */
    function app() {
        return \OffeneVergaben\Console\Application::getInstance();
    }
}

if (!function_exists('detect_utf_encoding')) {
    /**
     * Detect the encoding of a string based on the BOM (Byte Order Mark)
     *
     * @see https://www.php.net/manual/de/function.mb-detect-encoding.php#91051
     *
     * If the provided $text does not contain a BOM the detection will fail
     * and return null.
     *
     * @param $text
     * @return string|boolean - the detected encoding or false on failure
     */
    function detect_utf_encoding_from_bom($text) {

        // Unicode BOM is U+FEFF, but after encoded, it will look like this.
        $UTF32_BIG_ENDIAN_BOM    = chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF);
        $UTF32_LITTLE_ENDIAN_BOM = chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00);
        $UTF16_BIG_ENDIAN_BOM    = chr(0xFE) . chr(0xFF);
        $UTF16_LITTLE_ENDIAN_BOM = chr(0xFF) . chr(0xFE);
        $UTF8_BOM                = chr(0xEF) . chr(0xBB) . chr(0xBF);

        $first2 = substr($text, 0, 2);
        $first3 = substr($text, 0, 3);
        $first4 = substr($text, 0, 3);

        if ($first3 == $UTF8_BOM) return 'UTF-8';
        elseif ($first4 == $UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
        elseif ($first4 == $UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
        elseif ($first2 == $UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
        elseif ($first2 == $UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';

        return FALSE;
    }
}

if (!function_exists('convert_to_utf8')) {
    /**
     * Try to detect encoding based on BOM and convert to utf8
     *
     * @param $data
     * @throws Exception
     * @return bool|mixed|string
     */
    function convert_to_utf8($data) {

        $from = detect_utf_encoding_from_bom($data);

        if (!$from || $from === 'UTF-8') {
            return $data;
        }

        $out = false;

        if (function_exists('iconv')) {
            $out = iconv($from, 'utf-8', $data);
        }
        if ($out === false && function_exists('mb_convert_encoding')) {
            $out = mb_convert_encoding($data, 'utf-8', $from);
        }

        if ($out === false) {
            throw new Exception('Unsupported encoding %s. Please install iconv or mbstring for PHP.');
        }

        return $out;
    }
}

if (! function_exists('safe_urlencode')) {
    /**
     * Encode everything but reserved characters ( http://www.ietf.org/rfc/rfc3986.txt )
     * plus dot, dash, underscore and tilde (and percent to stop double encoding).
     *
     * Code shamelessly borrowed from https://stackoverflow.com/a/15427449/718980
     *
     * @param $txt
     * @return mixed
     */
    function safe_urlencode($txt){
        $result = preg_replace_callback("/[^-\._~:\/\?#\\[\\]@!\$&'\(\)\*\+,;=%]+/",
            function ($match) {
                return rawurlencode($match[0]);
            }, $txt);
        return ($result);
    }
}

if (! function_exists('safe_urldecode_german')) {
    /**
     *
     * Decode german special char encodings.
     *
     * @param $txt
     * @return mixed
     */
    function safe_urldecode_german($txt){
        $alphabet = [
            'ä' => '%C3%A4',
            'Ä' => '%C3%84',
            'ö' => '%C3%B6',
            'Ö' => '%C3%96',
            'ü' => '%C3%BC',
            'Ü' => '%C3%9C',
            'ß' => '%C3%9F',
            ' ' => '%20',       // rawurlencode version, not urlencode ('+')
        ];

        return str_replace(array_values($alphabet),array_keys($alphabet),$txt);
    }
}

if (! function_exists('is_url_encoded')) {
    /**
     * This is by no means a universal solution. The function tests only against
     * urlencoded special german characters.
     *
     * @param $url
     * @return bool
     */
    function is_url_encoded($url) {
        $alphabet = [
            'ä' => '%C3%A4',
            'Ä' => '%C3%84',
            'ö' => '%C3%B6',
            'Ö' => '%C3%96',
            'ü' => '%C3%BC',
            'Ü' => '%C3%9C',
            'ß' => '%C3%9F',
            ' ' => '%20',       // rawurlencode version, not urlencode ('+')
        ];

        foreach(array_values($alphabet) as $test) {
            if (strpos($url,$test) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('text_shorten')) {
    function text_shorten($text, $length = 40) {
        if (!$text) {
            return $text;
        }

        if (strlen($text) > $length) {
            return mb_substr($text,0,$length) . '...';
        } else {
            return $text;
        }
    }
}