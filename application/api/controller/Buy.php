<?php


namespace app\api\controller;

use comservice\Response;
use think\Request;
use app\admin\model\BuyModel;


class Buy extends BaseController
{
    public function  publish(Request $request){
        $describe = $request->post('describe','');
        $contact = $request->post('contact','');
        $level = $request->post('level',0);
        $part = $request->post('part',0);
        if(empty($describe) || $level==0 ){
            return json(Response::fail('参数错误'));
        }
        return json(BuyModel::publish($describe,$level,$part,$contact,$this->uid));
    }

}
