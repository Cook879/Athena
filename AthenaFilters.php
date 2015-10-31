<?php

/**
 * Filters used by Athena
 */
class AthenaFilters {

    /**
     * Checks type of user and their age
     * Returns -1 if anon, -2 if info not available, or otherwise returns the age of the account in minutes
     *
     * @return int
     */
    public static function userAge() {
        global $wgUser;

        // check anon
        $registration = $wgUser->getRegistration();
        if( $registration === false ) {
            // if false, user is anon
            return -1;
        } elseif ( $registration === null ) {
            // if null, user is registered but info not available
            return -2;
        } else {
            // we have a timestamp
            // get current time
            $now = wfTimestamp();
            // convert registration from MediaWiki timestamp to Unix timestamp
            $registration = wfTimestamp( TS_UNIX, $registration);
            // Get difference (in seconds)
            $diff = $now - $registration;
            // Convert to minutes, rounding down
            $diff = floor($diff / 60);
            return $diff;
        }
    }

    /**
     * Gets the number of external links in an article
     *
     * @param $text string
     * @return int
     */
    public static function numberOfLinks($text) {
        // Three ways to make a link
        // A: [http(s)://..... ]
        $count = preg_match_all("/\[http(s?):\/\/([^\[\]])+\]/", $text);
        // B: http(s)://...
        $count += preg_match_all("/[^\[]http(s?):\/\/[^\s^\]^\[]+[^\[]\s/", $text);
        // C: [//.... ]
        $count += preg_match_all("/\[\/\/([^\[\]])+\]/", $text);
        // Plus alternate protocols
        // D: [mailto:.... ]
        $count += preg_match_all("/\[mailto:([^\[\]])+\]/", $text);
        $count += preg_match_all("/[^\[]mailto:[^\s^\]^\[]+[^\[]\s/", $text);
        // E: [gopher:.... ]
        $count += preg_match_all("/\[gopher:([^\[\]])+\]/", $text);
        $count += preg_match_all("/[^\[]gopher:\/\/[^\s^\]^\[]+[^\[]\s/", $text);
        // F: [news:.... ]
        $count += preg_match_all("/\[news:([^\[\]])+\]/", $text);
        $count += preg_match_all("/[^\[]news:\/\/[^\s^\]^\[]+[^\[]\s/", $text);
        // G: [ftp:.... ]
        $count += preg_match_all("/\[ftp:([^\[\]])+\]/", $text);
        $count += preg_match_all("/[^\[]ftp:\/\/[^\s^\]^\[]+[^\[]\s/", $text);
        // H: [irc:.... ]
        $count += preg_match_all("/\[irc:([^\[\]])+\]/", $text);
        $count += preg_match_all("/[^\[]irc:\/\/[^\s^\]^\[]+[^\[]\s/", $text);

        return $count;
    }

    /**
     * Gets the number of (certain) syntax uses in an article
     *
     * @param $text string
     * @return int
     */
    public static function numberOfSyntax($text) {
        // Start with headings
        $count = preg_match_all("/==([^=]+)==(\s)*(\n|$)/", $text);
        $count += preg_match_all("/===([^=]+)===(\s)*(\n|$)/", $text);
        $count += preg_match_all("/====([^=]+)====(\s)*(\n|$)/", $text);
        $count += preg_match_all("/=====([^=]+)=====(\s)*(\n|$)/", $text);
        $count += preg_match_all("/======([^=]+)======(\s)*(\n|$)/", $text);
        // nowiki tags are very wiki specific
        $count += preg_match_all("/<nowiki>(.*)<\/nowiki>/", $text);
        $count += preg_match_all("/<nowiki\/>/", $text);
        // Internal links
        $count += preg_match_all("/\[\[([^\[\]])+\]\]/", $text);
        // Tables
        $count += preg_match_all("/\{\|([^\{\|\}])+\|\}/", $text);
        // Templates
        $count += preg_match_all("/\{\{([^\{\}])+\}\}/", $text);

        return $count;
    }

    /**
     * Compares the language of the site with the language of the edit
     * Returns true if the same, and false if different or null if error
     *
     * @param $text string
     * @return bool|null
     */
    public static function sameLanguage($text) {
        global $wgLanguageCode;

        $classifier = AthenaFilters::getClassifier();
        try {
            $language = $classifier->detectSimple($text);
        } catch (Text_LanguageDetect_Exception $e) {
            return null;
        }

        // Remove any region specialities from wiki's language code (e.g. en-gb becomes en)
        $arr = preg_split("/-/", $wgLanguageCode);

        echo( $language );

        if( $language !== null ) {
            if ($arr[0] === $language) {
                return true;
            }
            return false;
        }

        return null;
    }

    /**
     * Checks for broken spambot code
     * Determined based off of {blah|blah|blah} syntax and occurrences of #file_links<>
     *
     * @param $text string
     * @return int
     */
    public static function brokenSpamBot($text) {
        // Word choices
        $count = preg_match_all("/\{([^\{\}]|)+\}/", $text);
        // Link count
        $count += preg_match_all("/#file_links<>/", $text);
        return $count;
    }

    /**
     * Loads the language classifier
     * @return Text_LanguageDetect
     */
    static function getClassifier() {
        global $IP;

        // Code for Text-LanguageDetect
        require_once $IP . '\extensions\Athena\libs\Text_LanguageDetect-0.3.0\Text\LanguageDetect.php';
        $classifier = new Text_LanguageDetect;
        // Set it to return ISO 639-1 (same format as MediaWiki)
        $classifier->setNameMode(2);
        return $classifier;
    }

}
