<?php


namespace app\api\controller;


use app\admin\model\Activity as ActivityModel;
use app\admin\model\ActivityUser;


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

    /**
     * 参加活动
     * @param $id
     * @return string
     */
    public function joinIn($id){
        return json(ActivityUser::joinIn($id,$this->uid));
    }

    /**
     * 开奖
     * @return mixed
     */
    public function lottery(){
        return json(ActivityModel::lottery());
    }
}
