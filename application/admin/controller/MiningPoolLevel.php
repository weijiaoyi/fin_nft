<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class MiningPoolLevel extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MiningPoolLevel;

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        $mining_pool_id= input('mining_pool_id');
        $this->assignconfig('mining_pool_id', $mining_pool_id);
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['miningpool','goodsrank'])
                    ->where($where)
                    ->where('mining_pool_id', $mining_pool_id)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as $row) {
                $row->visible(['id','level','efficiency_start','efficiency_end','mining_pool_id','create_time']);
				$row->visible(['miningpool']);
				$row->getRelation('miningpool')->visible(['name']);
                $row->visible(['goodsrank']);
                $row->getRelation('goodsrank')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    public function add()
    {
        if(request()->isPost()){
            $mining_pool_id = $this->request->get('mining_pool_id');
            $post = input('post.');
            $row = $post['row'];
            //判断入已经添加了则不允许再添加
            $res = $this->model->where(['mining_pool_id'=>$mining_pool_id, 'level'=>$row['level']])->count();
            if($res)
            {
                return json(['code'=>0,'msg'=>'此等级已经添加!']);
            }
            $row['mining_pool_id'] = $mining_pool_id;
            $result = $this->model->insertGetId($row);
            if($result > 0)
            {
                return json(['code'=>1,'msg'=>'添加成功']);
            }
            return json(['code'=>0,'msg'=>'添加失败']);
        }
        return $this->fetch();
    }
}
