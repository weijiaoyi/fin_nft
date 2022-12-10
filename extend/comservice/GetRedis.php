<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/1
 * Time: 16:28
 */
namespace comservice;
use think\Config;
use think\Env;

class GetRedis
{
    public static function getRedis()
    {
        $redis = RedisCache::getInstance(Env::get('redis.host', '127.0.0.1'),Env::get('redis.password', ''));
        $redis->selectDb(0);
        return $redis;
    }
}
