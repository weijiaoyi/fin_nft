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
                    index_url: 'goods_manghe_users/index' + location.search,
                    add_url: 'goods_manghe_users/add',
                    edit_url: 'goods_manghe_users/edit',
                    del_url: 'goods_manghe_users/del',
                    //multi_url: 'goods_manghe_users/multi',
                    //import_url: 'goods_manghe_users/import',
                    table: 'goods_manghe_users',
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
                        /*{checkbox: true},*/
                        {field: 'id', title: __('Id')},
                        {field: 'users.nick_name', title: __('Users.nick_name'), operate: 'LIKE'},
                        //{field: 'user_id', title: __('User_id')},
                        {field: 'goods_number', title: __('Goods_number'), operate: 'LIKE'},
                        //{field: 'goods_id', title: __('Goods_id')},
                        {field: 'goods.name', title: __('Goods.name'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: function (val,row) {
                                if(val == 1)
                                {
                                    return '<span class="text-muted">'+__('Status 1')+'</span>';
                                }
                                else if(val == 2)
                                {
                                    return '<span class="text-danger">'+__('Status 2')+'</span>';
                                }
                                return '<span class="text-success">'+__('Status 3')+'</span>';
                        }},
                        //{field: 'goods.id', title: __('Goods.id')},
                        {field: 'goods.image', title: __('Goods.image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'users.member', title: __('Users.member'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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