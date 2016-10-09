<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Service;

use Eva\EvaEngine\Exception\OriginNotAllowedException;
use Phalcon\DI\InjectionAwareInterface;

class Cors implements InjectionAwareInterface
{

    protected $_di;

    protected $config;
    
    public function __construct($config)
    {
        $this->setConfig($config);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setDI($di)
    {
        $this->_di = $di;
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function simpleRequests()
    {
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            return;
        }
        if ($this->ifOriginIsSameAsHost()) {
            return;
        }
        if (! $this->ifHttpOriginIsInTheWhiteList()) {
            throw new OriginNotAllowedException('Http Origin Is Not Allowed');
        }
        $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
    }

    public function preflightRequests(
        $allowCredentials = 'true',
        $allowMethods = 'GET, POST, PUT, DELETE, OPTIONS',
        $allowHeaders = 'Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With'
    ) {
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            return;
        }
        if ($this->ifOriginIsSameAsHost()) {
            return;
        }

        if (! $this->ifHttpOriginIsInTheWhiteList()) {
            throw new OriginNotAllowedException('Http Origin Is Not Allowed');
        }
        $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Credentials', (string)$allowCredentials);
        $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
        $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Methods', $allowMethods);
        $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Headers', $allowHeaders);
        if (strtoupper($this->getDI()->getRequest()->getMethod()) == 'OPTIONS') {
            $this->getDI()->getResponse()->send();
            return;
        }
    }

    protected function ifHttpOriginIsInTheWhiteList()
    {
        $checked = false;
        foreach ($this->config as $domain) {
            $originDomainArray = explode('.', parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));
            $allowedDomainArray = explode('.', $domain['domain']);
            if (! (count($allowedDomainArray) > count($originDomainArray)) && ! array_diff($allowedDomainArray, $originDomainArray)) {
                $checked = true;
            }
        }
        return $checked;
    }

    public function ifOriginIsSameAsHost()
    {
        $originDomainArray = explode('.', parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));
        $hostDomainArray = explode('.', $_SERVER['HTTP_HOST']);
        if (! (count($hostDomainArray) > count($originDomainArray)) && ! array_diff($hostDomainArray, $originDomainArray)) {
            return true;
        }
        return false;
    }
}
