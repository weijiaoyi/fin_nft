define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                commonSearch: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'miningpool/index' + location.search,
                    add_url: 'miningpool/add',
                    edit_url: 'miningpool/edit',
                    del_url: 'miningpool/del',
                    multi_url: 'miningpool/multi',
                   config_url: 'goods_config/index',
                    import_url: 'miningpool/import',
                    table: 'miningpool',
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
                        {field: 'name', title: '矿池名称', operate: 'LIKE'},
                        {field: 'total_pool', title: '矿池总量', operate:false},
                        {field: 'image', title: '封面图', operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'status', title: '状态', searchList: {"0":'关闭',"1":'开启'}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    dropdown:'更多',
                                    name: '等级规则',
                                    text: '等级规则',
                                    title: '等级规则',
                                    classname: 'btn  btn-success btn-dialog',
                                    url: function (row) {
                                        return 'mining_pool_level/index?mining_pool_id='+row.id;
                                    },
                                    visible:function(row){
                                            return true; //或者return false
                                    },
                                    callback: function (data) {
                                        $(".btn-refresh").trigger("click");
                                    }
                                },
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
