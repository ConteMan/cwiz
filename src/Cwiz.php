<?php

namespace Boxiaozhi\Cwiz;


class Cwiz
{
    private $cwiz;

    public function __construct()
    {
        $this->cwiz = new CwizQueryService();
    }

    //用户信息
    public function userInfo()
    {
        return $this->cwiz->userInfo();
    }

    //分享笔记列表
    public function shares() {
        return $this->cwiz->shares();
    }

    //全部标签
    public function tags()
    {
        return $this->cwiz->tags();
    }

    //目录列表
    public function category()
    {
        return $this->cwiz->category();
    }

    /**
     * 笔记详情
     * @param $docGuid 笔记 Guid
     * @return mixed
     */
    public function noteDetail($docGuid)
    {
        return $this->cwiz->noteDetail($docGuid);
    }

    /**
     * 根据 标签Guid 获取笔记列表
     * @param $tagGuid 标签 Guid
     * @param int $start 开始位置
     * @param int $count 查询数量
     * @param string $orderBy 排序字段
     * @param string $ascending 正序，逆序
     * @return mixed
     */
    public function noteListByTag($tagGuid, $start=0, $count=50, $orderBy='modified', $ascending='desc')
    {
        return $this->cwiz->noteListByTag($tagGuid, $start, $count, $orderBy, $ascending);
    }

    /**
     * 根据目录获取笔记列表
     * @param $category
     * @param int $start
     * @param int $count
     * @param string $orderBy
     * @param string $ascending
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function noteListByCategory($category, $start=0, $count=50, $orderBy='modified', $ascending='desc')
    {
        return $this->cwiz->noteListByCategory($category, $start, $count, $orderBy, $ascending);
    }
}