define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'orders/index' + location.search,
                    add_url: 'orders/add',
                    edit_url: 'orders/edit',
                    del_url: 'orders/del',
                    multi_url: 'orders/multi',
                    import_url: 'orders/import',
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
                        {field: 'users.wallet_address',title:'授权地址', operate: 'LIKE'},
                        {field: 'goods.name', title: __('Goods.name'), operate: 'LIKE'},
                        {field: 'order_num', title: __('Order_num'), operate: 'LIKE'},
                        {field: 'price', title: __('Price'), operate:false},
                        //1=平台订单,2=二手交易订单,3=盲盒订单
                       // {field: 'order_type', title: '订单类型', searchList: {"1":'平台订单',"3":'盲盒订单',"4":'竞价订单',"5":'提现订单'}, formatter: Table.api.formatter.order_type},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5')}, formatter: Table.api.formatter.status},
                        //{field: 'pay_type', title: __('Pay_type'), searchList: {"0":__('Pay_type 0'),"1":__('Pay_type 1')}, formatter: Table.api.formatter.normal},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
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
