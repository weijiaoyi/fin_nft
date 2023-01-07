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
                    index_url: 'buy_contact/index' + location.search+'&buy_id='+Config.buy_id,
                    edit_url: 'buy_contact/edit',
                    del_url: 'buy_contact/del',
                    table: 'buy_contact',
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
                        {field: 'contact.name', title: '联系方式', operate: 'LIKE'},
                        {field: 'contact.image', title: '图片', operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'address', title: '联系地址'}
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
