define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                 fixedColumns: true,
                 fixedRightNumber: 1,
                extend: {
                    index_url: 'recharge_record/index' + location.search,
                    add_url: 'recharge_record/add',
                    edit_url: 'recharge_record/edit',
                    del_url: 'recharge_record/del',
                    multi_url: 'recharge_record/multi',
                    import_url: 'recharge_record/import',
                    table: 'recharge_record',
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
                        {field: 'id', title: __('Id'), operate:false},
                        {field:'users.wallet_address',title:'授权地址', operate: 'LIKE'},
                        {field: 'account', title: __('Account'), operate:false},
                        {field: 'address', title: '充值地址', operate:'LIKE'},
                        {field: 'currency.name', title: '币种', operate: false},
                        {field: 'currencyProtocol.protocols_name', title: '协议', operate: false},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5')}, formatter: Table.api.formatter.status},
                        {field: 'refuse', title: __('Refuse'), operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'pass',
                                    text: '通过',
                                    title: '确认通过充值吗',
                                    classname: 'btn btn-success btn-ajax',
                                    icon: 'fa fa-check',
                                    url: 'recharge_record/pass',
                                    confirm: '确认通过充值吗?',
                                    visible:function(row){
                                        if(row.status == 0){
                                            return true; //或者return false
                                        }
                                    },
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    }

                                },
                                {
                                    name: 'refuse',
                                    text: '拒绝',
                                    title: '拒绝充值',
                                    classname: 'btn  btn-primary btn-dialog',
                                    icon: 'fa fa-close',
                                    url: 'recharge_record/refuse',
                                    //  confirm: '确认拒绝实名认证吗?',
                                    visible:function(row){
                                        if(row.status == 0 || row.status == 3){
                                            return true; //或者return false
                                        }
                                    },
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    },
                                    callback: function (data) {
                                        $(".btn-refresh").trigger("click");
                                    }

                                },
                            ]}
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
        pass: function () {
            Controller.api.bindevent();
        },
        refuse: function () {
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
