define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'currency/index' + location.search,
                    add_url: 'currency/add',
                    edit_url: 'currency/edit',
                    del_url: 'currency/del',
                    multi_url: 'currency/multi',
                    import_url: 'currency/import',
                    table: 'currency',
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
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'status', title: '状态', searchList: {"0":'隐藏',"1":'显示'}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                buttons: [
                                {
                                    dropdown:'充值地址',
                                    name: '充值地址',
                                    text: '充值地址',
                                    title: '充值地址',
                                    classname: 'btn  btn-success btn-dialog',
                                    url: function (row) {
                                        return 'currency_protocol/index?currency_id='+row.id;
                                    },
                                    visible:function(row){
                                        return true; //或者return false
                                    },
                                    callback: function (data) {
                                        $(".btn-refresh").trigger("click");
                                    }
                                }
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
