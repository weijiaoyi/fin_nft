define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'staking/index' + location.search,
                    //add_url: 'staking/add',
                    edit_url: 'staking/edit',
                    //del_url: 'staking/del',
                    multi_url: 'staking/multi',
                    table: 'staking',
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
                        {field: 'users.nick_name', title: '质押用户', operate:'LIKE'},
                        {field: 'goodsrank.name', title: '等级', operate: false},
                        {field: 'level', title: '等级', visible:false,searchList:$.getJSON('goods/rank')},
                        {field: 'number', title: '质押数量', operate:false},
                        {field: 'income_num', title: '质押天数', operate:false},
                        {field: 'income', title: '累计收益', operate:false},
                        {field: 'status', title: '状态', searchList: {"0":'已赎回',"1":'进行中'}, formatter: Table.api.formatter.normal},
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
