define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'buy/index' + location.search,
                    add_url: 'buy/add',
                    edit_url: 'buy/edit',
                    del_url: 'buy/del',
                    multi_url: 'buy/multi',
                    import_url: 'buy/import',
                    table: 'orders',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'user_id',title:'求购者ID', operate: 'LIKE'},
                        {field: 'describe', title: '内容', operate: false},
                        {field: 'goodsrank.name', title: '等级', operate: false},
                        {field: 'level', title: '等级', visible:false,searchList:$.getJSON('goods/rank')},
                        {field: 'part', title: '类型 ', searchList: {'0':'成品',"1":'碎片1',"2":'碎片2',"3":'碎片3',"4":'碎片4'}, formatter: Table.api.formatter.status},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: '发布时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    dropdown:'查看联系方式',
                                    name: '查看联系方式',
                                    text: '查看联系方式',
                                    title: '查看联系方式',
                                    classname: 'btn  btn-success btn-dialog',
                                    url: function (row) {
                                        return 'buy_contact/index?buy_id='+row.id;
                                    },
                                    visible:function(row){
                                        if(row.remark){
                                            return true; //或者return false
                                        }
                                    },
                                    callback: function (data) {
                                        $(".btn-refresh").trigger("click");
                                    }
                                },
                                ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
