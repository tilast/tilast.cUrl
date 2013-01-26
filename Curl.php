<?php

/*
    Class for working with the Curl library
*/

class Curl
{
    /**
     * Handler of this curl connection
     * @var resource
     */
    private $handler;

    /**
     * Constructor
     * @param json-string with the curl parametrs $params
     */
    function __construct($params)
    {
        // initialize of curl
        $this->handler = curl_init();
        if(!$this->handler)
            throw new CurlInitException("Curl is not required to your server");

        // try getting parameters
        $paramsArray = json_decode($params, true);
        foreach($paramsArray as $key => $value)
        {
            // create the new exception if there is a outside parameter
            if(!defined("CURLOPT_".strtoupper($key)))
                throw new CurlParamsException("Such parameter is not exist");

            // creating of array for curl_setopt_array
            $paramsArray[constant("CURLOPT_".strtoupper($key))] = $value;
            // deleting of extra cells of array
            unset($paramsArray[$key]);
        }

        // setting of parameters
        curl_setopt_array($this->handler, $paramsArray);
    }

    /**
     * Execute of current curl query
     * Returns the string variable with the content
     */
    public function exec()
    {
        $res = curl_exec($this->handler);
        if($res === false)
            throw new CurlExecException("An error with execution of Curl query".curl_error($this->handler).'(['.curl_errno($this->handler).'])');
        return $res;
    }

    /**
     * Method-wrapper for getting info about current curl query
     */
    public function getInfo()
    {
        return curl_getinfo($this->handler);
    }

    /*
     * Destructor
     * */
    function __destruct()
    {
        curl_close($this->handler);
    }
}
/*
    На счёт исключений еще хочу поговорить с Андреем, не знаю, как их по-правильному заполнять и какую инфу передавать
 */
class CurlException extends Exception
{
    function __construct($message)
    {
        parent::__construct("problems with Curl query: ".$message);
    }
}
class CurlInitException extends CurlException
{
    function __construct($message)
    {
        parent::__construct("Init Exception, ".$message);
    }
}
class CurlParamsException extends CurlException
{
    function __construct($message)
    {
        parent::__construct("Params Exception, ".$message);
    }
}
class CurlExecException extends CurlException
{
    function __construct($message)
    {
        parent::__construct("Exec Exception, ".$message);
    }
}

/* Маленький пример работы */

try
{
$curl = new Curl
('{
    "url":"http://localhost/ukraine/",
    "returntransfer": true
}');
echo $curl->exec();
}
catch(CurlException $e)
{
    echo $e->getMessage();
}
