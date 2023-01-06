define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'manghe_goods/index' + location.search,
                    add_url: 'manghe_goods/add',
                    edit_url: 'manghe_goods/edit',
                    del_url: 'manghe_goods/del',
                    multi_url: 'manghe_goods/multi',
                   config_url: 'goods_config/index',
                    import_url: 'manghe_goods/import',
                    table: 'manghe_goods',
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
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'goodsrank.name', title: 'NFT等级', operate: false},
                        {field: 'part', title: '碎片', searchList: {'0':'成品',"1":'碎片1',"2":'碎片2',"3":'碎片3',"4":'碎片4'}, formatter: Table.api.formatter.status},
                        {field: 'level', title: 'NFT等级', visible:false,searchList:$.getJSON('manghe_goods/rank')},
                        {field: 'part', title: '碎片', searchList: {"0":'否',"1":'是'}, formatter: Table.api.formatter.normal},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'price', title: __('Price'), operate:false},
                        {field: 'start_time', title: __('Start_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'surplus', title: __('Surplus'), operate:false},
                        {field: 'is_show', title: __('Is_show'), searchList: {"0":__('Is_show 0'),"1":__('Is_show 1')}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate    }
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
