<?php


namespace app\api\controller;

use comservice\Response;
use think\Request;
use app\admin\model\BuyModel;


class Buy extends BaseController
{

    /**
     * 发布收购
     * @param Request $request
     * @return \think\response\Json
     */
    public function  publish(Request $request){
        $describe = $request->post('describe','');
        $contact = $request->post('contact/a','');
        $level = $request->post('level',0);
        $part = $request->post('part',0);
        if(empty($describe) || $level==0 ){
            return json(Response::fail('参数错误'));
        }
        return json(BuyModel::publish($describe,$level,$part,$contact,$this->uid));
    }

    /**
     * 收购列表
     * @param int $page
     * @param int $pagesize
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function buyList($status=1,$page=1,$pagesize=10){
        return json(BuyModel::buyList($status,$page,$pagesize));
    }

    /**
     * 收购详情
     * @param int $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details($id=0){
        return json(BuyModel::details($id));
    }



}
