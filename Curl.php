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
     * parameters you must write without "CURLOPT_"-prefix
     * @param json-string or array with the curl parameters $params
     * example of json-string: '{"url":"http://google.com.ua/"}'
     * example of array: array("url" => "http://google.com.ua/")
     */
    function __construct($params = null)
    {
        // initialize of curl
        if(!function_exists("curl_init"))
            throw new CurlInitException("Curl is not required to your server");

        // create handler of this curl connection
        $this->handler = curl_init();
        if(!$this->handler)
            throw new CurlInitException("Can't create handler of curl connection");

        if($params)
            $this->setParams($params);
    }


    /**
     * Sets parameters of curl query
     * parameters you must write without "CURLOPT_"-prefix
     * @param json-string or array with the curl parameters $params
     * example of json-string: '{"url":"http://google.com.ua/"}'
     * example of array: array("url" => "http://google.com.ua/")
     */
    public function setParams($params)
    {

        // checking of input data
        if(!$params)
            throw new CurlParamsException("An array of parameters is empty");

        // setting default parameters
        $paramsArray[constant("CURLOPT_RETURNTRANSFER")] = "1";
        $paramsArray[constant("CURLOPT_USERAGENT")] = "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17"; // will be changed

        // processing of input data
        $paramsArray = (is_array($params)) ? $params : json_decode($params, true);
        // array for wrong parameters, if they'll be exist
        $wrongParams = array();

        // try getting parameters
        foreach($paramsArray as $key => $value)
        {

            // write the wrong parameter into $wrongParams
            if(!defined("CURLOPT_".strtoupper($key)))
            {
                $wrongParams[] = $key;
                continue;
            }

            // creating of array for curl_setopt_array
            $paramsArray[constant("CURLOPT_".strtoupper($key))] = $value;

            // Remove unnecessary elements of the array. This string is important, because $paramsArray must have data for curl_setopt_array() function only
            unset($paramsArray[$key]);

        }

        // throwing of wrong parameters
        if(!empty($wrongParams))
        {
            $keys = implode(", ", $wrongParams);
            throw new CurlParamsException("parameter <b style='color: red'>".$keys."</b> is not exist");
        }

        // setting of parameters
        curl_setopt_array($this->handler, $paramsArray);

    }


    /**
     * @param $postArray - array for the POST-query
     * Method adds the post-query to this curl connection
     */
    public function setPost($postArray)
    {
        // check input data
        if(empty($postArray))
            throw new CurlPostException("post array is empty");
        else
        {
            $postArray = (is_array($postArray)) ? $postArray : json_decode($postArray, true);
            // create th post-query string
            $firstKey = key($postArray);
            $postStr = "";
            foreach($postArray as $key => $value)
            {
                $postStr .= ($firstKey == $key) ? $key."=".$value : "&".$key."=".$value;
            }
            // set parameter
            curl_setopt($this->handler, CURLOPT_POSTFIELDS, $postStr);
        }
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
    Exceptions
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
        parent::__construct("Execution Exception, ".$message);
    }
}
class CurlPostException extends CurlException
{
    function __construct($message)
    {
        parent::__construct("Creating POST-query Exception, ".$message);
    }
}


/* Little example of work */

/*try
{
    $curl = new Curl();
    $curl->setParams
    (
        //'{"url":"http://google.com.ua/"}'
        array("url" => "http://google.com.ua/", "post" => "1")
    );
    $curl->setPost
        (
            array("name" => "Ihor", "surname" => "Kroosh")
            // '{"name" : "Ihor", "surname" : "Kroosh"}'
        );
    echo $curl->exec();
}
catch(CurlException $e)
{
    echo $e->getMessage();
}*/
