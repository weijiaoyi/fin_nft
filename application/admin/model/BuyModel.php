<?php

namespace app\admin\model;

use comservice\Response;
use think\Model;


class BuyModel extends Model
{



    // 表名
    protected $name = 'buy';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;


    public static function publish($describe,$level,$part,$contact,$user_id){
        $add = [];
        $add['describe']= $describe;
        $add['level']= $level;
        $add['part']= $part;
        $add['user_id']= $user_id;
        $add['create_time']= date('Y-m-d H:i:s');
        $bid = self::insertGetId($add);
        if($bid){
            if(is_array($contact)){
                foreach($contact as &$vo) {
                    $contact = Contact::where('id',$vo['contact_id'])->find();
                    if(!$contact){
                        unset($vo);
                    }
                    $vo['buy_id'] = $bid;
                }
                BuyContact::insertAll($contact);
            }
            return Response::success('success');
        }else{
            return Response::fail('发布失败');
        }
    }

    public function user()
    {
        return $this->belongsTo('Users', 'user_id')->setEagerlyType(0);
    }

}
