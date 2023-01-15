<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 商品盲盒购买次数配置
 *
 * @icon fa fa-circle-o
 */
class CurrencyProtocol extends Backend
{

    /**
     * GoodsMangheConfig模型对象
     * @var \app\admin\model\GoodsMangheConfig
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\CurrencyProtocol;
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
        $currency_id = input('currency_id');
        $this->assignconfig('currency_id', $currency_id);
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
                ->with(['chainProtocol'])
                ->where('currency_id', $currency_id)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        if(request()->isPost()){
            $currency_id = $this->request->get('currency_id');
            $post = input('post.');
            $row = $post['row'];
            $row['currency_id'] = $currency_id;
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
