<?php


namespace app\api\controller;


use logicmodel\IndexLogic;
use think\Controller;
use think\Request;

class Index extends Controller
{
    private $indexLogic;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->indexLogic = new IndexLogic();
    }

    /**
     * 轮播图列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bannerList($type=1){
        return json($this->indexLogic->bannerList($type));
    }

    /**
     * 系统公告列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function noticeList(){
        return json($this->indexLogic->noticeList());
    }
    public function calendar(){
        return json($this->indexLogic->calendar());
    }

}