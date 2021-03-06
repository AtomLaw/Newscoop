<?php
/**
 * @package Campsite
 *
 * @author Klaus P. Pieper <klaus_p.pieper@t-online.de>
 * @copyright 2002 Klaus P. Pieper
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.sourcefabric.org
 */

/**
 * The cron class implementation.
 */
class Cron {

    /**
     * This is the heart of the class
     * @param time $tLast last time at which the command was completed
     * @param time $tNow  the reference time, usually the current time stamp
     * @param string $sSpec the specifier in the usual crontab format
     * @return boolean
     * TRUE if a timestamp exists between $tLast and $tNow fulfilling the $sSpec criteria.
     * returns FALSE otherwise
     */
    function due($tLast, $tNow, $sSpec)
    {

        // this array describes the classic crontab format
        // for internal use the elements are listed in reverse order
        $arSeg = array("wday", "mon",
                       "mday", "hours",
                       "minutes");
        // alternate crontab format includes year
        // this format is internally not (yet) supported!!!
        /* $arSeg = array("year", "wday",
                          "mon",  "mday",
                          "hours", "minutes");
        */

        // this array contains the offset in case for the carry over status
        // see below for the determination of the carry over status
        $arPeriod = array("wday"    => 7,
                          "mon"     => 12,
                          "mday"    =>
                              array(31, 28, 31, 30, 31, 30,
                                    31, 31, 30, 31, 30, 31),
                          "hours"   => 24,
                          "minutes" => 60);

        $arTime = array("wday"    =>    604800,  //   7 * 24 * 60 * 60
                        "mon"     =>  31536000,  // 365 * 24 * 60 * 60
                        "mday"    =>
                              array(31 * 86400,  //  31 * 24 * 60 * 60
                                    28 * 86400,
                                    31 * 86400,
                                    30 * 86400,
                                    31 * 86400,
                                    30 * 86400,
                                    31 * 86400,
                                    31 * 86400,
                                    30 * 86400,
                                    31 * 86400,
                                    30 * 86400,
                                    31 * 86400),
                        "hours"   =>     86400,  //       24 * 60 * 60
                        "minutes" =>      3600); //            60 * 60


        $iSeg = 0;        // segment index
        $iCmpVal = 0;     // compare value

        // these lines added in 0.2.5
        $bStatus = FALSE; // procedure status
        $iPFaktor = 0;    // period factor
        $iTFaktor = 0;    // time factor

        if ($tNow == NULL)  $tNow = time();
        // this line added in version 0.2.2
        if ($tLast == NULL) return FALSE;

        // convert strings to time
        if (is_string($tLast)) $tLast = strtotime($tLast);
        if (is_string($tNow))  $tNow = strtotime($tNow);

        if ($tNow < $tLast) return FALSE;

        // convert time variables to arrays
        $arLast = getdate($tLast);
        $arNow  = getdate($tNow);
        $arSpec = array_reverse(explode(" ", $sSpec));

        // walk through segments of crontab specifier
        for ($iSeg = 0; $iSeg < count($arSeg); $iSeg ++) {
            // obtain segment key
            $sSeg = $arSeg[$iSeg];
            // does specifier segment contain '*'?
            if (strstr($arSpec[$iSeg], "*") != FALSE) {
                // week days need special treatment
                if ($sSeg == "wday") $iCmpVal = $arLast[$sSeg];
                // use same segment of time reference
                else $iCmpVal = $arNow[$sSeg];
            // specifier segment contains specific criteria
            } else {
                // get reference value
                $iCmpVal = cron::_nextLowerVal($arSpec[$iSeg], $arNow[$sSeg]);
            } /* endif */

            // this section completely changed in 0.2.5
            // obtain period factor
            $iPFactor = $arPeriod[$sSeg];
            // numbers of days per month are always different ...
            if ($sSeg == "mday")
                $iPFactor = $iPFactor[$arLast["mon"]];

            // obtain period time factor
            $iTFactor = $arTime[$sSeg];
            // numbers of days per month are always different ...
            if ($sSeg == "mday")
                $iTFactor = $iTFactor[$arLast["mon"]];

            // this is the decisive part of the function:
            if ($arLast[$sSeg] < $iCmpVal &&
                $iCmpVal <= $arNow[$sSeg])
                { $bStatus = TRUE; }

            if (strstr($arSpec[$iSeg], "*") == FALSE) {
                // next two lines changed in 0.2.7
                if ((($bStatus == TRUE && $arNow[$sSeg] == $arLast[$sSeg]) ||
                     $arNow[$sSeg] < $arLast[$sSeg]) &&
                    $arLast[$sSeg] < $iCmpVal + $iPFactor &&
                    $iCmpVal + $iPFactor <= $arNow[$sSeg] + $iPFactor &&
                    $iCmpVal >= 0)
                    { $bStatus = TRUE; }
                else if ($tNow > $tLast + $iTFactor)
                    { $bStatus = TRUE; }
                // note that this condition causes a premature return:
                else if ($arLast[$sSeg] > $iCmpVal)
                    { return FALSE; }
                else if ($iCmpVal < $arNow[$sSeg] && $iCmpVal == $arLast[$sSeg] )
                    { return FALSE; }
            } /* endif */
            // end of section

        } /* endfor */

        return $bStatus;
    }

    /**
     * This function determines the highest number specified in the
     * crontab segment but smaller than the reference
     * @param string $sSpec segment of crontab specifier
     * @param int $iRef  the reference number
     * @return int
     * the number as described above, -1 if no number was found
     */
    function _nextLowerVal($sSpec, $iRef)
    {
        $arSpec1 = explode(",", $sSpec); // divide segment into details
        $arInt   = array();              // array of potential integers
        $arSpec2 = array();              // array of details if
                                         // specified as range
        $i   = 0;
        $sEl = "";

        // walk through list of details
        foreach($arSpec1 as $sEl) {
            // specified as range?
            if(strchr($sEl, "-") != FALSE) {
                // split again
                $arSpec2 = explode("-", $sEl);
                // add all numbers within range to list of integers
                for ($i = $arSpec2[0]; $i <= $arSpec2[1]; $i ++)
                    array_push($arInt, $i);
            } else { // not a range, add directly to list of integers
                array_push($arInt, $sEl);
            } /* endif */
        } /* endfor */

        // sort reverse, highest number is now 1st element
        rsort($arInt);
        // walk backwards through list
        foreach($arInt as $iEl) {
            // if element is smaller than reference, return element
            if ($iEl <= $iRef) return $iEl;
        } /* endfor */

        // no element found
        return -1;
    }
}

?>