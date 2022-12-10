<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class CalendarGoods extends Backend
{
    
    /**
     * CalendarGoods模型对象
     * @var \app\admin\model\CalendarGoods
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\CalendarGoods;
        $this->view->assign("isShowList", $this->model->getIsShowList());
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
        $calendar_id = input('ids');
        $this->assignconfig('calendar_id', $calendar_id);
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
            $calendar_id = input('calendar_id');
            $where1 = [];
            if(!empty($calendar_id) && $calendar_id != 'null '){
                $where1 = ['calendar_id'=>$calendar_id];
            }
            $list = $this->model
                    ->with(['goods','calendar'])
                    ->where(['calendar_goods.is_del'=>0])
                    ->where($where1)
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','order','is_show']);
                $row->visible(['goods']);
				$row->getRelation('goods')->visible(['name']);
				$row->visible(['calendar']);
				$row->getRelation('calendar')->visible(['start_time']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $this->assign('calendar_id',$calendar_id);
        return $this->view->fetch();
    }
    public function del($ids = "")
    {
        $result = $this->model->where(['id'=>['in',$ids]])->update(['is_del'=>1]);
        if($result) return json(['code'=>1,'msg'=>'删除成功']);
        return json(['code'=>0,'msg'=>'删除失败']);
    }
    public function add($calendar_id='')
    {
        if(request()->isPost()){
            $data  = input();
            $data = $data['row'];
            $data['calendar_id'] = $calendar_id;
            $result = $this->model->insertGetId($data);
            if($result) return json(['code'=>1,'msg'=>'添加成功']);
            return json(['code'=>0,'msg'=>'添加失败']);
        }
        return $this->fetch();
    }
}
