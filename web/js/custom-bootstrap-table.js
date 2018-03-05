(function($) {
    'use strict';

    var BootstrapTable = $.fn.bootstrapTable.Constructor;

    $.fn.bootstrapTable.methods.push('updateTimetable');

    BootstrapTable.prototype.updateTimetable = function (row) {
        var id = row.id || null;
        var customer_id = row.customer_id || null;
        var provider_id = row.provider_id || null;

        if (!id) {
            return;
        }

        var rowId = $.inArray(this.getRowByUniqueId(id), this.options.data);
        this.options.data[rowId] = row;

        if (customer_id) {
            for (var i = 0; i < this.options.data.length; i++) {
                if (this.options.data[i]['customer_id'] === customer_id) {
                    this.options.data[i]['customer_balance'] = row.customer_balance;
                    if (row._customer_balance_class) {
                        this.options.data[i]['_customer_balance_class'] = row._customer_balance_class;
                    }
                }

                if (this.options.data[i]['provider_id'] === provider_id) {
                    this.options.data[i]['provider_balance'] = row.provider_balance;
                    if (row._provider_balance_class) {
                        this.options.data[i]['_provider_balance_class'] = row._provider_balance_class;
                    }
                }
            }
        }

        this.initSearch();
        this.initPagination();
        this.initSort();
        this.initBody(true);
    };
})(jQuery);