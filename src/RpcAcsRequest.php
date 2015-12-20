<?php namespace Aliyun\Core;

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
use Aliyun\Core\Auth\Credential;
use Aliyun\Core\Auth\ISigner;

abstract class RpcAcsRequest extends AcsRequest
{

    private $dateTimeFormat = 'Y-m-d\TH:i:s\Z';


    public function  __construct($product, $version, $actionName)
    {
        parent::__construct($product, $version, $actionName);
        $this->initialize();
    }


    private function initialize()
    {
        $this->setMethod("GET");
        $this->setAcceptFormat("JSON");
    }


    /**
     * @param iSigner    $iSigner
     * @param Credential $credential
     * @param            $domain
     *
     * @return string
     */
    public function composeUrl($iSigner, $credential, $domain)
    {
        $apiParams                     = parent::getQueryParameters();
        $apiParams["RegionId"]         = $this->getRegionId();
        $apiParams["AccessKeyId"]      = $credential->getAccessKeyId();
        $apiParams["Format"]           = $this->getAcceptFormat();
        $apiParams["SignatureMethod"]  = $iSigner->getSignatureMethod();
        $apiParams["SignatureVersion"] = $iSigner->getSignatureVersion();
        $apiParams["SignatureNonce"]   = uniqid();
        date_default_timezone_set("GMT");
        $apiParams["Timestamp"] = date($this->dateTimeFormat);
        $apiParams["Action"]    = $this->getActionName();
        $apiParams["Version"]   = $this->getVersion();
        $apiParams["Signature"] = $this->computeSignature($apiParams, $credential->getAccessSecret(), $iSigner);
        $requestUrl             = $this->getProtocol() . "://" . $domain . "/?";

        foreach ($apiParams as $apiParamKey => $apiParamValue) {
            $requestUrl .= "$apiParamKey=" . urlencode($apiParamValue) . "&";
        }

        return substr($requestUrl, 0, -1);
    }


    /**
     * @param         $parameters
     * @param         $accessKeySecret
     * @param iSigner $iSigner
     *
     * @return mixed
     */
    private function computeSignature($parameters, $accessKeySecret, $iSigner)
    {
        ksort($parameters);
        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = parent::getMethod() . '&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
        $signature    = $iSigner->signString($stringToSign, $accessKeySecret . "&");

        return $signature;
    }


    protected function percentEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);

        return $res;
    }

}