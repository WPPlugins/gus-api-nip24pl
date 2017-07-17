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
 * NIP24 service client
 */
class NIP24Client
{
    const VERSION = '1.2.8';

    const DEFAULT_URL = 'https://www.nip24.pl/api';

    private $url;
    private $id;
    private $key;
    private $app;
    private $err;

    /**
     * NIP24 PSR-0 autoloader
     */
    public static function autoload($className)
    {
        $files = array(
            'PKD.php',
            'InvoiceData.php',
            'AllData.php',
            'VIESData.php',
            'NIP.php',
            'EUVAT.php',
            'REGON.php',
            'KRS.php',
            'Number.php'
        );
        
        foreach ($files as $file) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
            
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Register NIP24's PSR-0 autoloader
     */
    public static function registerAutoloader()
    {
        spl_autoload_register(__NAMESPACE__ . '\\NIP24Client::autoload');
    }

    /**
     * Construct new service client object
     * 
     * @param string $id
     *            NIP24 key identifier
     * @param string $key
     *            NIP24 key
     */
    public function __construct($id, $key)
    {
        $this->url = self::DEFAULT_URL;
        $this->id = $id;
        $this->key = $key;
        $this->app = '';
        $this->err = '';
    }

    /**
     * Set non default service URL
     * 
     * @param string $url
     *            service URL
     */
    public function setURL($url)
    {
        $this->url = $url;
    }

    /**
     * Set application info
     * 
     * @param string $app
     *            app info
     */
    public function setApp($app)
    {
        $this->app = $app;
    }

    /**
     * Check frim activity
     * 
     * @param string $nip
     *            NIP number
     * @return true|false
     */
    public function isActive($nip)
    {
        // clear error
        $this->err = '';
        
        // validate number
        if (! NIP::isValid($nip)) {
            $this->err = 'Numer NIP jest nieprawidłowy';
            return false;
        }
        
        // prepare url
        $url = ($this->url . '/check/' . NIP::normalize($nip));
        
        // send request
        $res = $this->get($url);
        
        if (! $res) {
            $this->err = 'Nie udało się nawiązać połączenia z serwisem NIP24';
            return false;
        }
        
        // parse response
        $doc = simplexml_load_string($res);
        
        if (! $doc) {
            $this->err = 'Odpowiedź serwisu NIP24 ma nieprawidłowy format';
            return false;
        }
        
        $code = $this->xpath($doc, '/result/error/code/text()');
        
        if (strlen($code) > 0) {
            $this->err = $this->xpath($doc, '/result/error/description/text()');
            return false;
        }
        
        // ok
        return true;
    }
    
    /**
     * Get invoice data for specified NIP number
     *
     * @param string $nip
     *            NIP number
     * @param bool $force
     *            false - get current data, true - force refresh
     * @return InvoiceData|false
     */
    public function getInvoiceData($nip, $force = false)
    {
        return $this->getInvoiceDataExt(Number::NIP, $nip, $force);
    }

    /**
     * Get invoice data for specified number type
     * 
     * @param int $type
     *            search number type as Number::xxx value
     * @param string $number
     *            search number value
     * @param bool $force
     *            false - get current data, true - force refresh
     * @return InvoiceData|false
     */
    public function getInvoiceDataExt($type, $number, $force = false)
    {
        // clear error
        $this->err = '';
        
        // validate number and construct path
        if (! ($suffix = $this->getPathSuffix($type, $number))) {
            return false;
        }
        
        $fun = ($force === true ? 'getf' : 'get');
        $url = ($this->url . '/' . $fun . '/invoice/' . $suffix);
        
        // send request
        $res = $this->get($url);
        
        if (! $res) {
            $this->err = 'Nie udało się nawiązać połączenia z serwisem NIP24';
            return false;
        }
        
        // parse response
        $doc = simplexml_load_string($res);
        
        if (! $doc) {
            $this->err = 'Odpowiedź serwisu NIP24 ma nieprawidłowy format';
            return false;
        }
        
        $code = $this->xpath($doc, '/result/error/code/text()');
        
        if (strlen($code) > 0) {
            $this->err = $this->xpath($doc, '/result/error/description/text()');
            return false;
        }
        
        $invoice = new InvoiceData();
        
        $invoice->nip = $this->xpath($doc, '/result/firm/nip/text()');
        
        $invoice->name = $this->xpath($doc, '/result/firm/name/text()');
        $invoice->firstname = $this->xpath($doc, '/result/firm/firstname/text()');
        $invoice->lastname = $this->xpath($doc, '/result/firm/lastname/text()');
        
        $invoice->street = $this->xpath($doc, '/result/firm/street/text()');
        $invoice->streetNumber = $this->xpath($doc, '/result/firm/streetNumber/text()');
        $invoice->houseNumber = $this->xpath($doc, '/result/firm/houseNumber/text()');
        $invoice->city = $this->xpath($doc, '/result/firm/city/text()');
        $invoice->postCode = $this->xpath($doc, '/result/firm/postCode/text()');
        $invoice->postCity = $this->xpath($doc, '/result/firm/postCity/text()');
        
        $invoice->phone = $this->xpath($doc, '/result/firm/phone/text()');
        $invoice->email = $this->xpath($doc, '/result/firm/email/text()');
        $invoice->www = $this->xpath($doc, '/result/firm/www/text()');
        
        return $invoice;
    }
    
    /**
     * Get all data for specified NIP number
     *
     * @param string $nip
     *            NIP number
     * @param bool $force
     *            false - get current data, true - force refresh
     * @return AllData|false
     */
    public function getAllData($nip, $force = false)
    {
        return $this->getAllDataExt(Number::NIP, $nip, $force);
    }

    /**
     * Get all data for specified number type
     * 
     * @param int $type
     *            search number type as Number::xxx value
     * @param string $number
     *            search number value
     * @param bool $force
     *            false - get current data, true - force refresh
     * @return AllData|false
     */
    public function getAllDataExt($type, $number, $force = false)
    {
        // clear error
        $this->err = '';
        
        // validate number and construct path
        if (! ($suffix = $this->getPathSuffix($type, $number))) {
            return false;
        }
        
        $fun = ($force === true ? 'getf' : 'get');
        $url = ($this->url . '/' . $fun . '/all/' . $suffix);
        
        // send request
        $res = $this->get($url);
        
        if (! $res) {
            $this->err = 'Nie udało się nawiązać połączenia z serwisem NIP24';
            return false;
        }
        
        // parse response
        $doc = simplexml_load_string($res);
        
        if (! $doc) {
            $this->err = 'Odpowiedź serwisu NIP24 ma nieprawidłowy format';
            return false;
        }
        
        $code = $this->xpath($doc, '/result/error/code/text()');
        
        if (strlen($code) > 0) {
            $this->err = $this->xpath($doc, '/result/error/description/text()');
            return false;
        }
        
        $data = new AllData();
        
        $data->type = $this->xpath($doc, '/result/firm/type/text()');
        $data->nip = $this->xpath($doc, '/result/firm/nip/text()');
        $data->regon = $this->xpath($doc, '/result/firm/regon/text()');
        
        $data->name = $this->xpath($doc, '/result/firm/name/text()');
        $data->shortname = $this->xpath($doc, '/result/firm/shortname/text()');
        $data->firstname = $this->xpath($doc, '/result/firm/firstname/text()');
        $data->secondname = $this->xpath($doc, '/result/firm/secondname/text()');
        $data->lastname = $this->xpath($doc, '/result/firm/lastname/text()');
        
        $data->street = $this->xpath($doc, '/result/firm/street/text()');
        $data->streetNumber = $this->xpath($doc, '/result/firm/streetNumber/text()');
        $data->houseNumber = $this->xpath($doc, '/result/firm/houseNumber/text()');
        $data->city = $this->xpath($doc, '/result/firm/city/text()');
        $data->community = $this->xpath($doc, '/result/firm/community/text()');
        $data->county = $this->xpath($doc, '/result/firm/county/text()');
        $data->state = $this->xpath($doc, '/result/firm/state/text()');
        $data->postCode = $this->xpath($doc, '/result/firm/postCode/text()');
        $data->postCity = $this->xpath($doc, '/result/firm/postCity/text()');
        
        $data->phone = $this->xpath($doc, '/result/firm/phone/text()');
        $data->email = $this->xpath($doc, '/result/firm/email/text()');
        $data->www = $this->xpath($doc, '/result/firm/www/text()');
        
        $data->creationDate = $this->xpathDate($doc, '/result/firm/creationDate/text()');
        $data->startDate = $this->xpathDate($doc, '/result/firm/startDate/text()');
        $data->registrationDate = $this->xpathDate($doc, '/result/firm/registrationDate/text()');
        $data->holdDate = $this->xpathDate($doc, '/result/firm/holdDate/text()');
        $data->renevalDate = $this->xpathDate($doc, '/result/firm/renevalDate/text()');
        $data->lastUpdateDate = $this->xpathDate($doc, '/result/firm/lastUpdateDate/text()');
        $data->endDate = $this->xpathDate($doc, '/result/firm/endDate/text()');
        
        $data->registryEntityCode = $this->xpath($doc, '/result/firm/registryEntity/code/text()');
        $data->registryEntityName = $this->xpath($doc, '/result/firm/registryEntity/name/text()');
        
        $data->registryCode = $this->xpath($doc, '/result/firm/registry/code/text()');
        $data->registryName = $this->xpath($doc, '/result/firm/registry/name/text()');
        
        $data->recordCreationDate = $this->xpathDate($doc, '/result/firm/record/created/text()');
        $data->recordNumber = $this->xpath($doc, '/result/firm/record/number/text()');
        
        $data->basicLegalFormCode = $this->xpath($doc, '/result/firm/basicLegalForm/code/text()');
        $data->basicLegalFormName = $this->xpath($doc, '/result/firm/basicLegalForm/name/text()');
        
        $data->specificLegalFormCode = $this->xpath($doc, '/result/firm/specificLegalForm/code/text()');
        $data->specificLegalFormName = $this->xpath($doc, '/result/firm/specificLegalForm/name/text()');
        
        $data->ownershipFormCode = $this->xpath($doc, '/result/firm/ownershipForm/code/text()');
        $data->ownershipFormName = $this->xpath($doc, '/result/firm/ownershipForm/name/text()');
        
        for ($i = 1;; $i ++) {
            $code = $this->xpath($doc, '/result/firm/PKDs/PKD[' . $i . ']/code/text()');
            
            if (! $code) {
                break;
            }
            
            $descr = $this->xpath($doc, '/result/firm/PKDs/PKD[' . $i . ']/description/text()');
            $pri = $this->xpath($doc, '/result/firm/PKDs/PKD[' . $i . ']/primary/text()');
            
            $pkd = new PKD();
            
            $pkd->code = $code;
            $pkd->description = $descr;
            $pkd->primary = ($pri == 'true' ? true : false);
            
            array_push($data->pkd, $pkd);
        }
        
        return $data;
    }

    /**
     * Get VIES data for specified number
     *
     * @param string $euvat
     *            EU VAT number with 2-letter country prefix
     * @return VIESData|false
     */
    public function getVIESData($euvat)
    {
        // clear error
        $this->err = '';
    
        // validate number and construct path
        if (! ($suffix = $this->getPathSuffix(Number::EUVAT, $euvat))) {
            return false;
        }
    
        $url = ($this->url . '/get/vies/' . $suffix);
    
        // send request
        $res = $this->get($url);
    
        if (! $res) {
            $this->err = 'Nie udało się nawiązać połączenia z serwisem NIP24';
            return false;
        }
    
        // parse response
        $doc = simplexml_load_string($res);
    
        if (! $doc) {
            $this->err = 'Odpowiedź serwisu NIP24 ma nieprawidłowy format';
            return false;
        }
    
        $code = $this->xpath($doc, '/result/error/code/text()');
    
        if (strlen($code) > 0) {
            $this->err = $this->xpath($doc, '/result/error/description/text()');
            return false;
        }
    
        $vies = new VIESData();
    
        $vies->countryCode = $this->xpath($doc, '/result/vies/countryCode/text()');
        $vies->vatNumber = $this->xpath($doc, '/result/vies/vatNumber/text()');
        
        $vies->valid = ($this->xpath($doc, '/result/vies/valid/text()') == 'true' ? true : false);
        
        $vies->traderName = $this->xpath($doc, '/result/vies/traderName/text()');
        $vies->traderCompanyType = $this->xpath($doc, '/result/vies/traderCompanyType/text()');
        $vies->traderAddress = $this->xpath($doc, '/result/vies/traderAddress/text()');
        
        return $vies;
    }
    
    /**
     * Update contact data for specified number type
     * 
     * @param int $type
     *            search number type as Number::xxx value
     * @param string $number
     *            search number value
     * @param string $phone
     *            phone
     * @param string $email
     *            email
     * @param string $www
     *            home page URL
     * @return true|false
     */
    public function updateContactData($nip, $phone, $email, $www)
    {
        // clear error
        $this->err = '';
        
        // validate number
        if (! NIP::isValid($nip)) {
            $this->err = 'Numer NIP jest nieprawidłowy';
            return false;
        }
        
        // prepare url
        $url = ($this->url . '/update/' . NIP::normalize($nip));
        
        // prepare content
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n"
            . "<update>\r\n"
            . "  <firm>\r\n"
            . "    <phone>" . htmlspecialchars($phone, ENT_XML1, 'UTF-8') . "</phone>\r\n"
            . "    <email>" . htmlspecialchars($email, ENT_XML1, 'UTF-8') . "</email>\r\n"
            . "    <www>" . htmlspecialchars($www, ENT_XML1, 'UTF-8') . "</www>\r\n"
            . "  </firm>\r\n"
            . "</update>";
        
        // send request
        $res = $this->post($url, 'text/xml', $xml);
        
        if (! $res) {
            $this->err = 'Nie udało się nawiązać połączenia z serwisem NIP24';
            return false;
        }
        
        // parse response
        $doc = simplexml_load_string($res);
        
        if (! $doc) {
            $this->err = 'Odpowiedź serwisu NIP24 ma nieprawidłowy format';
            return false;
        }
        
        $code = $this->xpath($doc, '/result/error/code/text()');
        
        if (strlen($code) > 0) {
            $this->err = $this->xpath($doc, '/result/error/description/text()');
            return false;
        }
        
        // ok
        return true;
    }

    /**
     * Get last error message
     * 
     * @return string error message
     */
    public function getLastError()
    {
        return $this->err;
    }

    /**
     * Prepare authorization header content
     * 
     * @param string $method
     *            HTTP method
     * @param string $url
     *            target URL
     * @return string|false
     */
    private function auth($method, $url)
    {
        // parse url
        $u = parse_url($url);
        
        if (! array_key_exists('port', $u)) {
            $u['port'] = ($u['scheme'] == 'https' ? '443' : '80');
        }
        
        // prepare auth header
        $nonce = bin2hex(openssl_random_pseudo_bytes(4));
        $ts = time();
        
        $str = "" . $ts . "\n"
            . $nonce . "\n"
            . $method . "\n"
            . $u['path'] . "\n"
            . $u['host'] . "\n"
            . $u['port'] . "\n"
            . "\n";
        
        $mac = base64_encode(hash_hmac('sha256', $str, $this->key, true));
        
        if (! $mac) {
            return false;
        }
        
        return 'Authorization: MAC id="' . $this->id . '", ts="' . $ts . '", nonce="' . $nonce . '", mac="' . $mac . '"';
    }

    /**
     * Prepare user agent information header content
     * 
     * @return string
     */
    private function userAgent()
    {
        return 'User-Agent: ' . (! empty($this->app) ? $this->app . ' ' : '') . 'NIP24Client/' . self::VERSION
            . ' PHP/' . phpversion();
    }

    /**
     * Set some common CURL options
     * 
     * @param cURL $curl            
     */
    private function setCurlOpt($curl)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            // curl on a windows does not know where to look for certificates
            // use local info downloaded from https://curl.haxx.se/docs/caextract.html
            curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem');
        }
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    }

    /**
     * Get result of HTTP GET request
     * 
     * @param string $url
     *            target URL
     * @return result|false
     */
    private function get($url)
    {
        // auth
        $auth = $this->auth('GET', $url);
        
        if (! $auth) {
            return false;
        }
        
        // headers
        $headers = array(
            $this->userAgent(),
            $auth
        );
        
        // send request
        $curl = curl_init();
        
        if (! $curl) {
            return false;
        }
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $this->setCurlOpt($curl);
        
        $res = curl_exec($curl);
        
        if (! $res) {
            return false;
        }
        
        curl_close($curl);
        
        return $res;
    }

    /**
     * Get result of HTTP POST request
     * 
     * @param string $url
     *            target URL
     * @param string $type
     *            content mime type
     * @param string $content
     *            content body
     * @return result|false
     */
    private function post($url, $type, $content)
    {
        // auth
        $auth = $this->auth('POST', $url);
        
        if (! $auth) {
            return false;
        }
        
        // headers
        $headers = array(
            $this->userAgent(),
            $auth,
            'Content-Type: ' . $type
        );
        
        // send request
        $curl = curl_init();
        
        if (! $curl) {
            return false;
        }
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        $this->setCurlOpt($curl);
        
        $res = curl_exec($curl);
        
        if (! $res) {
            return false;
        }
        
        curl_close($curl);
        
        return $res;
    }

    /**
     * Get element content as text
     * 
     * @param SimpleXMLElement $doc
     *            XML document
     * @param string $path
     *            xpath string
     * @return string
     */
    private function xpath($doc, $path)
    {
        $a = $doc->xpath($path);
        
        if (! $a) {
            return '';
        }
        
        if (count($a) != 1) {
            return '';
        }
        
        return trim($a[0]);
    }

    /**
     * Get element content as date in format yyyy-mm-dd
     * 
     * @param SimpleXMLElement $doc
     *            XML document
     * @param string $path
     *            xpath string
     * @return string output date
     */
    private function xpathDate($doc, $path)
    {
        $val = $this->xpath($doc, $path);
        
        if (empty($val)) {
            return '';
        }
        
        return date('Y-m-d', strtotime($val));
    }
    
    /**
     * Get path suffix
     *
     * @param int $type
     *            search number type as Number::xxx value
     * @param string $number
     *            search number value
     * @return string|false
     */
    private function getPathSuffix($type, $number)
    {
        $path = '';
        
        if ($type == Number::NIP) {
            if (! NIP::isValid($number)) {
                $this->err = 'Numer NIP jest nieprawidłowy';
                return false;
            }
        
            $path = 'nip/' . NIP::normalize($number);
        } else if ($type == Number::REGON) {
            if (! REGON::isValid($number)) {
                $this->err = 'Numer REGON jest nieprawidłowy';
                return false;
            }
        
            $path = 'regon/' . REGON::normalize($number);
        } else if ($type == Number::KRS) {
            if (! KRS::isValid($number)) {
                $this->err = 'Numer KRS jest nieprawidłowy';
                return false;
            }
        
            $path = 'krs/' . KRS::normalize($number);
        } else if ($type == Number::EUVAT) {
            if (! EUVAT::isValid($number)) {
                $this->err = 'Numer EU VAT ID jest nieprawidłowy';
                return false;
            }
        
            $path = 'euvat/' . EUVAT::normalize($number);
        } else {
            $this->err = 'Nieprawidłowy typ numeru';
            return false;
        }
        
        return $path;
    }
}

/* EOF */
