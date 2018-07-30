<?php

namespace Boxiaozhi\Cwiz;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Redis;
use \Exception;

class CwizQueryService
{
    private $redis = '';
    private $client = null;
    private $prefix = '';
    private $token = '';
    private $userKey = '';
    private $userInfo = [];

    const CLIENT_OPTIONS = [
        'clientType'    => 'web',
        'clientVersion' => '3.0.0',
        'apiVersion'    => '10',
    ];

    public function __construct()
    {
        $userId   = config('cwiz.username');
        $password = config('cwiz.password');
        if(!$userId || !$password){
            throw new Exception('请检查用户名和密码设置', 500);
        }
        //缓存
        $this->redis = Redis::connection(config('cwiz.redis_config'));
        $this->prefix = config('cwiz.cache_prefix');
        $this->userKey = $this->prefix.'user';
        //请求配置
        $baseUrl = 'https://note.wiz.cn';
        $this->client = new GuzzleClient(['base_uri' => $baseUrl]);
        //登录校验
        $this->loginCheck($userId, $password);
    }

    /**
     * 登录校验，无效则登录
     * @param $userId
     * @param $password
     * @return bool
     */
    private function loginCheck($userId, $password)
    {
        $user = $this->user();
        if($user){
            $keepRes = $this->loginKeep($user['token']);
            if($keepRes){
                $this->token = $user['token'];
                $this->userInfo = $user;
                return true;
            }
        }
        return $this->login($userId, $password);
    }

    /**
     * 保持登录状态
     * @param $token
     * @return bool
     */
    private function loginKeep($token)
    {
        $method = 'GET';
        $uri    = '/as/user/keep';
        $query  = [
            'token' => $token
        ];
        $param = [
            'query' => $query + self::CLIENT_OPTIONS
        ];
        $response = $this->client->request($method, $uri, $param);
        $res = json_decode($response->getBody(), true);
        return $res['returnCode'] == 200 ? true : false;
    }

    /**
     * 登录
     * @param $userId
     * @param $password
     * @return mixed
     */
    public function login($userId, $password)
    {
        $method = 'POST';
        $uri    = '/as/user/login';
        $param  = [
            'json' => [
                'userId'   => $userId,
                'password' => $password
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        $response = json_decode($response->getBody(), true);

        if($response['returnCode'] != 200){
            throw new Exception('登录失败，请检查');
        }

        $this->redis->set($this->userKey, json_encode($response['result']));
        $this->token = $response['result']['token'];
        $this->userInfo = $response['result'];
        return $response['result'];
    }

    /**
     * 缓存获取登录信息
     * @return mixed
     */
    public function user()
    {
        $user = $this->redis->get($this->userKey);
        return $user ? json_decode($user, true) : [];
    }

    /**
     * 接口获取用户信息
     * @return mixed
     */
    public function userInfo()
    {
        $userInfo = $this->userInfo;

        $method = 'GET';
        $uri    = '/wizas/a/users/get_info';
        $param  = [
            'query' => [
                'token'   => $userInfo['token'],
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }

    /**
     * 分享列表
     * @param int $page
     * @param int $size
     * @return mixed|\Psr\Http\Message\StreamInterface
     */
    public function shares($page=0, $size=50)
    {
        $userInfo = $this->userInfo;

        $method = 'GET';
        $uri    = '/share/api/shares';
        $param  = [
            'query' => [
                'token'   => $userInfo['token'],
                'kb_guid' => $userInfo['kbGuid'],
                'page'    => $page,
                'size'    => $size
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }

    /**
     * 根据 docGuid 获取笔记详情
     * @param $docGuid
     * @return mixed
     */
    public function noteDetail($docGuid)
    {
        $userInfo = $this->userInfo;

        $method = 'GET';
        $uri    = '/ks/note/download/'.$userInfo['kbGuid'].'/'.$docGuid;
        $param  = [
            'query' => [
                'token'        => $userInfo['token'],
                'downloadInfo' => 1, //笔记简介
                'downloadData' => 1, //笔记详情
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }

    /**
     * 全部标签信息
     * @return mixed
     */
    public function tags()
    {
        $userInfo = $this->userInfo;

        $method = 'GET';
        $uri    = '/ks/tag/all/'.$userInfo['kbGuid'];
        $param  = [
            'query' => [
                'token' => $userInfo['token']
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }

    /**
     * 目录列表
     * /Deleted Items/ 前缀的为删除的目录
     * @return mixed
     */
    public function category()
    {
        $userInfo = $this->userInfo;

        $method = 'GET';
        $uri    = '/ks/category/all/'.$userInfo['kbGuid'];
        $param  = [
            'query' => [
                'token' => $userInfo['token']
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }

    /**
     * 根据 tagGuid 获取笔记列表
     * @return mixed
     */
    public function noteListByTag($tagGuid, $start=0, $count=50, $orderBy='modified', $ascending='desc')
    {
        $userInfo = $this->userInfo;

        $method = 'GET';
        $uri    = '/ks/note/list/tag/'.$userInfo['kbGuid'];
        $param  = [
            'query' => [
                'token'     => $userInfo['token'],
                'tag'       => $tagGuid,
                'start'     => $start,
                'count'     => $count,
                'orderBy'   => $orderBy,
                'ascending' => $ascending,
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }
}