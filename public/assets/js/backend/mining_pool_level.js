define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'mining_pool_level/index' + location.search+'&mining_pool_id='+Config.mining_pool_id,
                    add_url: 'mining_pool_level/add'+ location.search+'&mining_pool_id='+Config.mining_pool_id,
                    edit_url: 'mining_pool_level/edit',
                    del_url: 'mining_pool_level/del',
                    multi_url: 'mining_pool_level/multi',
                   config_url: 'goods_config/index',
                    import_url: 'mining_pool_level/import',
                    table: 'mining_pool_level',
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
                        {field: 'miningpool.name', title: '矿池名称', operate: 'LIKE'},
                        {field: 'goodsrank.name', title: '等级', operate: false},
                        //{field: 'level', title: '等级', visible:false,searchList:$.getJSON('goods/rank')},
                        {field: 'efficiency_start', title: '效率开始区间', operate: false},
                        {field: 'efficiency_end', title: '效率截止区间', operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
