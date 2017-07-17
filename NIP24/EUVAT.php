<?php
/**
 * Copyright 2015-2017 NETCAT (www.netcat.pl)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author NETCAT <firma@netcat.pl>
 * @copyright 2015-2017 NETCAT (www.netcat.pl)
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace NIP24;

/**
 * EU VAT number verificator
 */
class EUVAT
{
    /**
     * Normalizes form of the VAT number
     * 
     * @param string $nip
     *            input string
     * @return string|false
     */
    public static function normalize($nip)
    {
        if (is_null($nip) || strlen($nip) <= 2) {
            return false;
        }
        
        $nip = str_replace(array(' ', '-'), '', $nip);
        $nip = trim($nip);

        $cc = strtoupper(substr($nip, 0, 2));
        $num = strtoupper(substr($nip, 2));
        
        if ($cc == 'AT') {
            // 9 chars
            if (preg_match('/[0-9A-Z]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'BE') {
            // 10 digits
            if (preg_match('/[0-9]{10}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'BG') {
            // 9 or 10 digits
            if (preg_match('/[0-9]{9,10}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'CY') {
            // 9 chars
            if (preg_match('/[0-9A-Z]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'CZ') {
            // 8-10 digits
            if (preg_match('/[0-9]{8,10}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'DE') {
            // 9 digits
            if (preg_match('/[0-9]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'DK') {
            // 8 digits
            if (preg_match('/[0-9]{8}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'EE') {
            // 9 digits
            if (preg_match('/[0-9]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'EL') {
            // 9 digits
            if (preg_match('/[0-9]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'ES') {
            // 9 chars
            if (preg_match('/[0-9A-Z]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'FI') {
            // 8 digits
            if (preg_match('/[0-9]{8}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'FR') {
            // 11 chars
            if (preg_match('/[0-9A-Z]{11}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'GB') {
            // 5-12 chars
            if (preg_match('/[0-9A-Z]{5,12}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'HR') {
            // 11 digits
            if (preg_match('/[0-9]{11}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'HU') {
            // 8 digits
            if (preg_match('/[0-9]{8}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'IE') {
            // 8-9 chars
            if (preg_match('/[0-9A-Z]{8,9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'IT') {
            // 11 digits
            if (preg_match('/[0-9]{11}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'LT') {
            // 9-12 digits
            if (preg_match('/[0-9]{9,12}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'LU') {
            // 8 digits
            if (preg_match('/[0-9]{8}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'LV') {
            // 11 digits
            if (preg_match('/[0-9]{11}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'MT') {
            // 8 digits
            if (preg_match('/[0-9]{8}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'NL') {
            // 12 chars
            if (preg_match('/[0-9A-Z]{12}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'PL') {
            // 10 digits
            if (preg_match('/[0-9]{10}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'PT') {
            // 9 digits
            if (preg_match('/[0-9]{9}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'RO') {
            // 2-10 digits
            if (preg_match('/[0-9]{2,10}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'SE') {
            // 12 digits
            if (preg_match('/[0-9]{12}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'SI') {
            // 8 digits
            if (preg_match('/[0-9]{8}/', $num) != 1) {
                return false;
            }
        } else if ($cc == 'SK') {
            // 10 digits
            if (preg_match('/[0-9]{10}/', $num) != 1) {
                return false;
            }
        } else {
            return false;
        }
        
        return $nip;
    }

    /**
     * Checks if specified NIP is valid
     * 
     * @param string $nip
     *            input number
     * @return bool
     */
    public static function isValid($nip)
    {
        if (! ($nip = self::normalize($nip))) {
            return false;
        }
        
        $cc = strtoupper(substr($nip, 0, 2));
        $num = strtoupper(substr($nip, 2));
        
        if ($cc == 'PL') {
            return NIP::isValid($num);    
        }
        
        return true;
    }
}

/* EOF */
