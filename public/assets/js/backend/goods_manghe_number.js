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
                    index_url: 'goods_manghe_number/index' + location.search+'&goods_id='+Config.goods_id,
                    add_url: 'goods_manghe_number/add'+ location.search+'&goods_id='+Config.goods_id,
                    edit_url: 'goods_manghe_number/edit',
                    del_url: 'goods_manghe_number/del',
                    //multi_url: 'goods_manghe_number/multi',
                    //import_url: 'goods_manghe_number/import',
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
                       /* {checkbox: true},*/
                        {field: 'id', title: __('Id')},
                        {field: 'goods.name', title: '盲盒', operate: 'LIKE'},
                        {field: 'number', title: '次数'},
                        {field: 'amount', title: '价格'},
                        {field: 'gift_times', title: '赠送次数'},
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
