<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-27
 * Time: 18:11
 */

namespace Boxiaozhi\Cwiz;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;

class CwizService
{
    private $client = null;
    private $prefix = '';
    private $token = '';
    private $userKey = '';

    const CLIENT_OPTIONS = [
        'clientType'    => 'web',
        'clientVersion' => '3.0.0',
        'apiVersion'    => '10',
    ];

    public function __construct()
    {
        $userId   = config('cwiz.username');
        $password = config('cwiz.password');

        $this->prefix = config('cwiz.cache_prefix');
        $this->userKey = $this->prefix.'user';
        $baseUrl = 'https://note.wiz.cn';

        $this->client = new GuzzleClient(['base_uri' => $baseUrl]);

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
        $user = Cache::get($this->userKey, '');
        $user = (array)json_decode($user, true);
        if($user){
            $keepRes = $this->loginKeep($user['token']);
            if($keepRes){
                $this->token = $user['token'];
                return true;
            }
        }
        $this->login($userId, $password);
    }

    /**
     * 保持登录状态
     * @param $token
     * @return bool
     */
    private function loginKeep($token)
    {
        $method = 'GET';
        $uri    = 'as/user/keep';
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

        if (Cache::has($this->userKey)) {
            Cache::forget($this->userKey);
        }
        Cache::forever($this->userKey, json_encode($response['result']));
        $this->token = $response['result']['token'];
        return $response['result'];
    }

    /**
     * 获取登录信息
     * @return mixed
     */

    public function user()
    {
        $user = Cache::get($this->userKey, '');
        return json_decode($user, true);
    }

    /**
     * 分享列表
     * @param int $page
     * @param int $size
     * @return mixed|\Psr\Http\Message\StreamInterface
     */
    public function shares($page=0, $size=50)
    {
        $loginInfo = $this->user();

        $method = 'GET';
        $uri    = '/share/api/shares';
        $param  = [
            'query' => [
                'token'   => $loginInfo['token'],
                'kb_guid' => $loginInfo['kbGuid'],
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
        $loginInfo = $this->user();

        $method = 'GET';
        $uri    = '/ks/note/download/'.$loginInfo['kbGuid'].'/'.$docGuid;
        $param  = [
            'query' => [
                'token'        => $loginInfo['token'],
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
        $loginInfo = $this->user();

        $method = 'GET';
        $uri    = '/ks/tag/all/'.$loginInfo['kbGuid'];
        $param  = [
            'query' => [
                'token' => $loginInfo['token']
            ]
        ];
        $response = $this->client->request($method, $uri, $param);
        return json_decode($response->getBody(), true);
    }
}