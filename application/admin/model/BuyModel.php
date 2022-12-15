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

    /**
     * 发布收购
     * @param $describe
     * @param $level
     * @param $part
     * @param $contactArr
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function publish($describe,$level,$part,$contactArr,$user_id){
        $add = [];
        $add['describe']= $describe;
        $add['level']= $level;
        $add['part']= $part;
        $add['user_id']= $user_id;
        $add['create_time']= date('Y-m-d H:i:s');
        $bid = self::insertGetId($add);
        if($bid){
            if(is_array($contactArr)){
                foreach($contactArr as &$vo) {
                    $contact = Contact::find($vo['contact_id']);
                    if(!$contact){
                        unset($vo);
                    }
                    $vo['buy_id'] = $bid;
                }
                BuyContact::insertAll($contactArr);
            }
            return Response::success('success');
        }else{
            return Response::fail('发布失败');
        }
    }


    /**
     * 收购列表
     * @param int $page
     * @param int $pagesize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function buyList($status=1,int $page, int $pagesize)
    {
        $where['g.status'] = $status;
        $count = self::alias('g')->where($where)->count();
        if ($count <= 0){
            return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        }
        $field = ['g.*','gr.image as rank_image', 'u.nick_name','u.head_image','u.rank_id as user_level'];
        $data = self::alias('g')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->join('users u', 'g.user_id = u.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['g.create_time desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, [ 'head_image','rank_image']);
            return Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
        }
        return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
    }

    /**
     * 收购详情
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function details($id){
        $where['g.status'] = 1;
        $where['g.id'] = $id;
        $field = ['g.*,gr.image as rank_image,u.nick_name,u.head_image,u.rank_id as user_level'];
        $data = self::alias('g')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->join('users u', 'g.user_id = u.id','LEFT')
            //->join('rank r', 'u.rank_id = r.id','LEFT')
            ->where($where)
            ->field($field)
            ->find();
        if ($data) {
            $data = $data->toArray();
            $buyContactList = BuyContact::alias('bc')
                ->join('contact c', 'bc.contact_id = c.id','LEFT')
                ->where('bc.buy_id',$data['id'])
                ->field('bc.address,c.name contact_name,c.image as contact_image')
                ->select();
            $buyContactList = collection($buyContactList)->toArray();
            $data['contact'] = addWebSiteUrl($buyContactList, [ 'contact_image']);
            $data = addWebSiteUrl($data, [ 'head_image','rank_image']);
            return Response::success('success', $data);
        }
        return Response::success('暂无数据');
    }

    public function user()
    {
        return $this->belongsTo('Users', 'user_id')->setEagerlyType(0);
    }

    public function contact()
    {
        return $this->belongsTo('BuyContact', 'id')->setEagerlyType(0);
    }

}
