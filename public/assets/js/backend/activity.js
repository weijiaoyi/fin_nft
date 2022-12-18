define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'activity/index' + location.search,
                    add_url: 'activity/add',
                    edit_url: 'activity/edit',
                    del_url: 'activity/del',
                    multi_url: 'activity/multi',
                   config_url: 'activity_config/index',
                    import_url: 'activity/import',
                    table: 'activity',
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
                        {field: 'title', title: '活动标题', operate: 'LIKE'},
                        {field: 'goods.name', title: '盲盒', operate: false},
                        {field: 'goods_id', title: '盲盒', visible:false,searchList:$.getJSON('activity/goods')},
                        {field: 'start_time', title: '开始时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'end_time', title: '截止时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'bonus', title: '奖金', operate:false},
                        {field: 'participants', title: '参与数', operate:false},
                       //{field: 'probability', title: '中奖率(%)', operate:false},
                        {field: 'status_text', title: '状态', operate: false},
                        {field: 'status', title: '状态',visible:false, searchList: $.getJSON('activity/status'), formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate               }
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
