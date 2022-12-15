<?php


namespace app\api\controller;

use comservice\Response;
use think\Request;
use app\admin\model\Activity as ActivityModel;


class Activity extends BaseController
{

    /**
     * 列表
     * @return \think\response\Json
     */
    public function activityList(){
        return json(ActivityModel::activityList());
    }

    /**
     * 详情
     * @param int $id
     * @return \think\response\Json
     */
    public function details($id=0){
        return json(ActivityModel::details($id));
    }



}
