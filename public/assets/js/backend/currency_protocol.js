define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                commonSearch:false,
                search:false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                extend: {
                    index_url: 'currency_protocol/index' + location.search+'&currency_id='+Config.currency_id,
                    add_url: 'currency_protocol/add'+ location.search+'&currency_id='+Config.currency_id,
                    edit_url: 'currency_protocol/edit',
                    del_url: 'currency_protocol/del',
                    //multi_url: 'currency_protocol/multi',
                    //import_url: 'currency_protocol/import',
                    table: 'goods_manghe_number',
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
                        {field: 'id', title: __('Id')},
                        //{field: 'currency_name', title: '币种', operate: 'LIKE'},
                        {field: 'chain_protocol.name', title: '链上协议', operate: 'LIKE'},
                        {field: 'in_address',width: 300, title: '充值地址'},
                        {field: 'is_open', title: '状态', searchList: {"0":'未开启',"1":'开启'}, formatter: Table.api.formatter.normal},
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
