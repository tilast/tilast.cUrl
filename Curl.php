<?php

/*
    Класс для работы с библиотекой cUrl
*/

class Curl
{
    /**
     * Дескриптор текущего curl-соединения
     * @var resource
     */
    private $handler;


    /**
     * Массив стандартных настроек
     * @var array
     */
    private static $defaultSet = array
    (
        "useragent" => "Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17",
        "returntransfer" => "1"
    );

    /**
     * Constructor
     * параметры должны быть написаны без "CURLOPT_"-префикса
     * @param json-строка или массив с curl-параметрами $params
     * пример json-строки: '{"url":"http://google.com.ua/"}'
     * пример массива: array("url" => "http://google.com.ua/")
     */
    function __construct($params = null)
    {
        // инициализация curl
        if(!function_exists("curl_init"))
            throw new CurlInitException("Curl is not required to your server");

        // создания дескриптора этого curl-соединения
        $this->handler = curl_init();
        if(!$this->handler)
            throw new CurlInitException("Can't create handler of curl connection");

        // установка параметров по-умолчанию(можно изменить)
        $this->setParams(self::$defaultSet);

        if($params)
            $this->setParams($params);
    }


    /**
     * Устанавливает параметры текущего curl-соединения
     * параметры должны быть написанны без "CURLOPT_"-префикса
     * @param json-строка или массив с curl-параметрами $params
     * пример json-строки: '{"url":"http://google.com.ua/"}'
     * пример массива: array("url" => "http://google.com.ua/")
     */
    public function setParams($params)
    {

        // проверка входящих данных
        if(!$params)
            throw new CurlParamsException("An array of parameters is empty");

        // обработка входных данных
        $paramsArray = (is_array($params)) ? $params : json_decode($params, true);

        if(!is_array($paramsArray))
            throw new CurlParamsException("wrong json-string");

        // массив для ошибочных параметров, если они будут
        $wrongParams = array();

        // пытаемя получить параметры
        foreach($paramsArray as $key => $value)
        {
            // смотрим, не массив ли это для post-запроса
            if($key == "postfields")
            {
                $value = $this->setPost($value);
            }

            // записываем неверные параметры в $wrongParams
            if(!defined("CURLOPT_".strtoupper($key)))
            {
                $wrongParams[] = $key;
                continue;
            }

            // создаем массив для curl_setopt_array
            $paramsArray[constant("CURLOPT_".strtoupper($key))] = $value;

            // Удаляем ненужные елементы массива. Эта строчка важна, ведь $paramsArray должен вмещать инфу только для функции curl_setopt_array()
            unset($paramsArray[$key]);

        }

        // отловка неверных параметров
        if(!empty($wrongParams))
        {
            $keys = implode(", ", $wrongParams);
            throw new CurlParamsException("parameters ".$keys." not exist");
        }

        // установка параметров
        curl_setopt_array($this->handler, $paramsArray);

    }

    /**
     * Устанавливает параметр для текущего curl-соединения
     * параметры должны быть написанны без "CURLOPT_"-префикса
     * @param $paramName название параметра
     * @param $paramValue значение
     * если $paramName == "postfields", то $paramValue должен быть передан как массив или json-строка с инфой для post-апроса
     */

    public function setParam($paramName, $paramValue)
    {
        if(!empty($paramName))
        {
            // если "postfields" - обрабатывает соответственным образом
            if($paramName == "postfields")
            {
                $paramValue = $this->setPost($paramValue);
            }
            // если нет - проверяем, существует ли такой параметр
            else
            {
                if(!defined("CURLOPT_".strtoupper($paramName)))
                    throw new CurlParamsException("parameter ".$paramName." is not exist");
            }
            // и устанавливаем параметр
            curl_setopt($this->handler, constant("CURLOPT_".strtoupper($paramName)), $paramValue);
        }
    }

    /**
     * Преображает массив или json-стрроку в строку curl-post запроса
     * @param $paramValue массив или json-строка с post-инфой
     * если $paramName == "postfields", то $paramValue должен быть передан как массив или json-строка с инфой для post-апроса
     */

    private function setPost($paramValue)
    {
        // приводим к виду массива значение, если это возможно
        $paramValue = (is_array($paramValue)) ? $paramValue : json_decode($paramValue, true);
        if(!is_array($paramValue))
            throw new CurlParamsException("wrong json-string or array");

        // создаем строку post-запроса
        $notFirstKey = false;
        $postStr = "";
        foreach($paramValue as $key => $value)
        {
            if($notFirstKey)
                $postStr .= "&";

            $postStr .= urlencode($key)."=".urlencode($value);

            $notFirstKey = true;
        }
        return $postStr;
    }

    /**
     * Выполнение текущего curl-запроса
     * Returns возвращает строку с запрошенной инфой или ошибку
     */
    public function exec()
    {
        $res = curl_exec($this->handler);
        if($res === false)
            throw new CurlExecException("An error with execution of Curl query".curl_error($this->handler).'(['.curl_errno($this->handler).'])');
        return $res;
    }

    /**
     * Метод-обертка функции curl_getinfo() для получения инфы о текущем curl запросе
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
    Исключения
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

/* Маленький пример работы */
/*
try
{
    $curl = new Curl();
    $curl->setParams
    (
        //'{"url":"http://google.com.ua/"}'
        array("url" => "http://google.com.ua/", "post" => "true", "postfields" => array("name" => "John", "surname" => "Smith", "anotherWay" => "можно также передавать не массив для post, а json-строку"))
    );
    //$curl->setParam("postfields", array("name" => "John", "surname" => "Smith"));
    echo $curl->exec();
}
catch(CurlException $e)
{
    echo $e->getMessage();
}*/
