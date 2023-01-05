define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'goods_users/index' + location.search,
                    add_url: 'goods_users/add',
                    edit_url: 'goods_users/edit',
                    del_url: 'goods_users/del',
                    multi_url: 'goods_users/multi',
                    import_url: 'goods_users/import',
                    table: 'goods_users',
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
                        {field: 'id', title: __('Id'), operate:false},
                        {field: 'users.wallet_address', title: '授权地址', operate: 'LIKE'},
                        {field: 'goods.name', title:'NFT', operate: 'LIKE'},
                        {field: 'level', title: '等级', visible:false,searchList:$.getJSON('goods/rank')},
                        {field: 'part', title: '类型 ', searchList: {'0':'成品',"1":'碎片1',"2":'碎片2',"3":'碎片3',"4":'碎片4'}, formatter: Table.api.formatter.status},
                       // {field: 'goods_number', title: __('Goods_number'), operate: 'LIKE'},
                        {field: 'price', title: __('Price'), operate:false},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5'),"6":__('Status 6')}, formatter: Table.api.formatter.status},
                        {field: 'is_show', title: __('Is_show'), searchList: {"0":__('Is_show 0'),"1":__('Is_show 1')}, formatter: Table.api.formatter.normal},
                        {field: 'order', title: __('Order'), operate:false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},


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
