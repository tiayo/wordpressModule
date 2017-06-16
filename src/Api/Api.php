<?php

namespace Foru\Api;

use Foru\Model\Options;
use Requests;

class Api
{
    protected $option;
    protected $option_value;
    protected $create_token;

    public function __construct()
    {
        $this->option = new Options();
        $this->create_token = 'https://192.168.10.236:8888/api/password-grant/';
    }

    /**
     * 从数据库读取token.
     *
     * @return string
     */
    public function view()
    {
        //取值
        $this->option_value = $this->option
            ->select('option_value')
            ->where('option_name', 'api_key')
            ->first();

        //如果不存在返回空
        if (empty($this->option_value->option_value)) {
            return null;
        }

        return $this->option_value['option_value'];
    }

    /**
     * 更新token.
     */
    public function updateKey()
    {
        //获取key
        try{
            $option_value = $this->createToken();
        } catch (\Exception $e) {
            return false;
        }

        //查询是否存在
        $count = $this->option
            ->where('option_name', 'api_key')
            ->count();

        //新建或更新
        if ($count >= 1) {
            $this->option
                ->where('option_name', 'api_key')
                ->update([
                    'option_value' => $option_value ?: null,
                ]);
        } else {
            $this->option
                ->create([
                    'option_name' => 'api_key',
                    'option_value' => $option_value ?: null,
                ]);
        }

        return true;
    }

    /**
     * 当提交为空时插入默认值
     */
    public function defaultKey()
    {
        $this->option->create([
            'option_value' => 'Entry token',
            'option_name' => 'api_key',
        ]);
    }

    /**
     * 请求创建店铺
     *
     * @return string
     */
    public function createToken()
    {
        $username = strip_tags($_POST['username']);
        $password = strip_tags($_POST['password']);

        $result = Requests::post(
            $this->create_token,
            array(
                'Accept' => 'application/json',
                'Authorization' => "Basic " . base64_encode($username . ':' . $password),
            ),
            array(
                'auth' => array($username, $password),
                'name' => get_bloginfo('name'),
                'site' => $this->format_url(get_home_url(), true),
                'platform' => 'WooCommerce',
            ),
            array(
                'verify' => false,
            )
        );

        //状态码非200抛错
        if ($result->status_code !=200) {
            throw new \Exception('error');
        }

        return json_decode($result->body, true)['token'];
    }

    /**
     *	规范化 URL
     *	判断是否使用 HTTPS 链接，当是 HTTPS 访问时候自动添加
     *	自动添加链接前面的 http://
     *	$slash 是判断是否要后面添加斜杠
     */
    public function format_url($url, $slash)
    {
        if (substr($url,0,4) != 'http' && substr($url,0,5) != 'https') {
            @$if_https = $_SERVER['HTTPS'];	//这样就不会有错误提示
            if ($if_https) {	//如果是使用 https 访问的话就添加 https
                $url='https://'.$url;
            } else {
                $url='http://'.$url;
            }
        }
        if ($slash) {
            $url = rtrim($url,'/').'/';
        }
        return $url;
    }
}
