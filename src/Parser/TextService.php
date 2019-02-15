<?php

namespace App\Parser;

class TextService
{
    /**
     * @param string[] $searchReplace
     * @param string $subject
     * @return mixed|string
     */
    private function str_replace_wrapper(array $searchReplace, string $subject) {
        foreach ($searchReplace as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
        }

        return $subject;
    }

    /**
     * @param string $text
     * @return string
     */
    public function stripInvalidChars(string $text): string
    {
        $regex = <<<'END'
/
  (
	(?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
	|   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
	|   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
	|   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
	){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
        $text = preg_replace($regex, '$1', $text);

        // unify strange apostrophes
        $text = str_replace(['`', '´', '’',], '\'', $text);

        // unify strange dashes
        /** @noinspection CascadeStringReplacementInspection */
        $text = str_replace(['‐', '‑', '‒', '–', '—', '―', '⁻', '₋', '−', '－'], '-', $text);

        // completely remove dirty chars
        /** @noinspection CascadeStringReplacementInspection */
        $text = str_replace(['¸', '', ''], '', $text);

        return $text;
    }

    public function cleanText(string $text)
    {
        $text = $this->stripInvalidChars($text);

        // fix encoding
        $text = html_entity_decode($text, ENT_COMPAT/* | ENT_HTML401*/, 'UTF-8');

        // remove multiple spaces
        $text = preg_replace('/( )+/', ' ', $text);

        // unify html line breaks
        $text = preg_replace('/(<)[brBR]+( )*(\/)*(>)/', '<br>', $text);

        // replace configured words (make a sort before to avoid replacing substuff and breaking things)
        $searchReplace = Strings::SEARCH_REPLACE;
        arsort($searchReplace);
        $text = $this->str_replace_wrapper($searchReplace, $text);

        // remove EU allergy warnings
        $text = preg_replace('/(( |\(|,|;)+[ABCDEFGHLMNOPR]( |\)|,|;)+)+/', ' ', " ${text} ");

        // FIXME is this still needed?
        $words_trim = ['oder', 'Vegan'];
        foreach ($words_trim as $word) {
            // e.g. "2. oder Kabeljaufilet" => "2. Kabeljaufilet"
            $text = preg_replace("/([0-9]+)([\. ]*)(${word})/", '${1}${2}', $text);
            // remove word on ending
            $text = preg_replace("/(${word})(\n)/", '${2}', $text);
        }

        // trim different types of characters and special whitespaces / placeholders
        $text = trim($text, "á .*\t\n\r\0-<> "); // no unicode chars should be used here to avoid problems

        return $text;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return bool|int
     */
    public function strposAfter(string $haystack, string $needle, int $offset = 0)
    {
        $pos = mb_strpos($haystack, $needle, $offset);
        if ($pos !== false) {
            $pos += mb_strlen($needle);
        }

        return $pos;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public function startsWith(string $haystack, string $needle): bool
    {
        return (mb_strpos($haystack, $needle) === 0);
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public function endsWith(string $haystack, string $needle): bool
    {
        $strlen = mb_strlen($haystack);
        $needlelen = mb_strlen($needle);
        if ($needlelen > $strlen) {
            return false;
        }
        return substr_compare($haystack, $needle, -$needlelen) === 0;
    }

    /**
     * @param string $haystack
     * @param string[] $needles
     * @param bool $case_insensitive
     * @return bool
     */
    public function stringsExist(string $haystack, array $needles, bool $case_insensitive = false): bool
    {
        foreach ($needles as $needle) {
            if ($case_insensitive && mb_stripos($haystack, $needle) !== false) {
                //error_log("'${needle}' exists in '${haystack}'");
                return true;
            }

            if (!$case_insensitive && mb_strpos($haystack, $needle) !== false) {
                //error_log("'${needle}' exists in '${haystack}'");
                return true;
            }
        }
        return false;
    }
}