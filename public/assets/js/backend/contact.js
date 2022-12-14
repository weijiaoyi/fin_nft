define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'contact/index' + location.search,
                    add_url: 'contact/add',
                    edit_url: 'contact/edit',
                    del_url: 'contact/del',
                    multi_url: 'contact/multi',
                    import_url: 'contact/import',
                    table: 'contact',
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
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: '名称', operate: 'LIKE'},
                        {field: 'image', title: '图片', operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'create_time', title: '创建时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'is_show', title: '是否显示', searchList: {"0":'隐藏',"1":'显示'}, formatter: Table.api.formatter.normal},
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
