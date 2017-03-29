<?php

/**
 * Curl类
 * Class Curl
 * 从自己很老的的框架里挖出来的Curl类
 */
class Curl
{
    protected $instance; // curl实例

    // 默认UA
    const UA = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36';

    /**
     * Curl constructor.
     * @param string $url 链接
     * @param array  $header
     * @throws Exception
     */
    public function __construct($url, array $header = [])
    {
        if ( !function_exists('curl_init')) throw new Exception('curl_init函数不存在，请确认相应扩展已正确加载', 1);
        if (empty($header)) $header = [self::UA, 'Content-Type: application/json'];

        $this->instance = curl_init($url);
        $this->set(CURLOPT_RETURNTRANSFER, 1);
        $this->set(CURLOPT_FOLLOWLOCATION, 1);
        $this->set(CURLOPT_HTTPHEADER, $header);
        $this->set(CURLOPT_SSL_VERIFYPEER, FALSE);
        $this->setTimeOut(60);
    }

    /**
     * 设置HTTP头
     * @param array $header
     * @return $this
     */
    public function setHeader(array $header)
    {
        $this->set(CURLOPT_HTTPHEADER, $header);
        return $this;
    }

    /**
     * curl_setopt
     * @param $opt
     * @param $v
     * @return $this
     */
    public function set($opt, $v)
    {
        curl_setopt($this->instance, $opt, $v);
        return $this;
    }

    /**
     * 执行curl
     * @return mixed
     */
    public function exec()
    {
        return curl_exec($this->instance);
    }

    /**
     * get方法执行curl
     * @return mixed
     */
    public function get()
    {
        return $this->exec();
    }

    public function __toString()
    {
        return $this->exec();
    }

    /**
     * 转发所有请求到$this->instance
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->instance, $name], $arguments);
    }

    /**
     * post方法执行curl
     * @param string|array $data
     * @return mixed
     */
    public function post($data)
    {
        $this->set(CURLOPT_POST, 1);

        if (is_array($data)){
            $this->set(CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            $this->set(CURLOPT_POSTFIELDS, $data);
        }
        return $this->exec();
    }

    /**
     * 添加Cookie
     * @param string|array $cookie
     * @return $this
     */
    public function addCookie($cookie)
    {
        if (is_array($cookie)){
            $r = '';
            foreach ($cookie as $key => $value){
                $r .= "{$key}={$value}; ";
            }
        } else {
            $r = $cookie;
        }
        $this->set(CURLOPT_COOKIE, $r);
        return $this;
    }

    /**
     * 读取Cookie值
     * @param string $text 网页Header信息（CURLOPT_HEADER设为1）
     * @param bool   $info 是否获取Cookie详细信息，默认为假
     * @return array
     */
    public static function readCookies($text, $info = FALSE)
    {
        preg_match_all("/set\-cookie:(.*)/i", $text, $result);
        if (count($result[1]) == 0) return array();

        $cookie_list = array();
        foreach ($result[1] as $cookie){
            $cookie_ary = explode(';', $cookie);
            $cookie = trim($cookie_ary[0]);
            $wz = stripos($cookie, '=');
            $key = substr($cookie, 0, $wz);

            if ($info){
                $ary = array();
                foreach ($cookie_ary as $value){
                    $value = trim($value);
                    $wz = stripos($value, '=');
                    $ary[substr($value, 0, $wz)] = substr($value, $wz + 1);
                }

                $cookie_list[$key] = $ary;
            } else {
                $cookie_list[$key] = substr($cookie, $wz + 1);
            }
        }

        return $cookie_list;
    }

    /**
     * 发起请求并获取Cookie
     * @param bool $postdata POST数据，为false时发起GET请求
     * @param bool $info     是否获取Cookie详细信息，默认为假
     * @return array
     */
    public function getCookies($postdata = FALSE, $info = FALSE)
    {
        $this->set(CURLOPT_HEADER, 1);
        if ($postdata !== FALSE){
            return self::readCookies($this->post($postdata), $info);
        } else {
            return self::readCookies($this->exec(), $info);
        }
    }

    /**
     * curl_getinfo
     * @param $opt
     * @return mixed
     */
    public function getInfo($opt)
    {
        return curl_getinfo($this->instance, $opt);
    }

    /**
     * curl_errno
     * @return int
     */
    public function errorNo()
    {
        return curl_errno($this->instance);
    }

    /**
     * curl_error
     * @return string 错误信息
     */
    public function errorMsg()
    {
        return curl_error($this->instance);
    }

    /**
     * 返回一个带错误代码的curl错误信息
     * @return string 错误信息
     */
    public function error()
    {
        return '#' . $this->errorNo() . ' - ' . $this->errorMsg();
    }

    /**
     * 关闭curl
     */
    public function close()
    {
        curl_close($this->instance);
    }

    /**
     * 设置超时时间 单位：秒
     * @param int $time 单位：秒
     * @return $this
     */
    public function setTimeOut($time)
    {
        $this->set(CURLOPT_TIMEOUT, $time);
        return $this;
    }

    /**
     * 销毁类时释放curl资源
     */
    public function __destruct()
    {
        $this->close();
    }
}