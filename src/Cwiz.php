<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-27
 * Time: 18:00
 */

namespace Boxiaozhi\Cwiz;


class Cwiz
{
    private $cwiz;

    public function __construct()
    {
        $this->cwiz = new CwizService();
    }

    public function nav() {
        $shares = $this->cwiz->shares();
        $data   = [];
        foreach($shares['content'] as $share){
            $data[] = [
                'to'   => '/note/' . $share['documentGuid'],
                'name' => $share['documentGuid'],
                'type' => '',
                'des'  => $share['title']
            ];
        }
        $default = isset($shares['content'][0]['documentGuid']) ? $shares['content'][0]['documentGuid'] : []; //默认笔记
        $res = ['default' => $default, 'data' => $data];
        return response()->json($res);
    }
}