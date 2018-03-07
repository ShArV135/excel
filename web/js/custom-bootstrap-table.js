(function($) {
    'use strict';

    var BootstrapTable = $.fn.bootstrapTable.Constructor,
        _initBody = BootstrapTable.prototype.initBody,
        _init = BootstrapTable.prototype.init,
        _horizontalScroll = BootstrapTable.prototype.horizontalScroll,
        _onColumnSearch = BootstrapTable.prototype.onColumnSearch
    ;

    BootstrapTable.prototype.init = function () {
        _init.apply(this, Array.prototype.slice.apply(arguments));

        var that = this;

        this.$el.on('load-success.bs.table', function () {
            if (localStorage.filterColumnsDefaults) {
                var filterColumnsDefaults = JSON.parse(localStorage.filterColumnsDefaults);
                that.$fixedHeader.find('input, select').each(function () {
                    var field = $(this).closest('[data-field]').data('field');

                    if (filterColumnsDefaults.hasOwnProperty(field)) {
                        $(this).val(filterColumnsDefaults[field]);
                    }
                });
                that.initColumnSearch(filterColumnsDefaults);
                that.initSearch();
                that.updatePagination();
            }
        });

    };

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

    BootstrapTable.prototype.initBody = function () {
        _initBody.apply(this, Array.prototype.slice.apply(arguments));

        $('.tt-tooltip').tooltip({
            trigger: 'click',
            container: 'body'
        });

        this.$tableBody.find('.times-comment').editable({
            container: 'body',
            display: function() {
                return '';
            },
            success : function (response, newValue) {
                if (newValue == '') {
                    $(this).addClass('text-muted');
                } else {
                    $(this).removeClass('text-muted');
                }
            }
        });

        this.$tableBody.selectable({
            filter: 'div.times-item'
        });

        $('.timetable td').hover(function() {
            var index = $(this).index() + 1;
            $('.timetable th:nth-child(' + index + ')').addClass('hover');
        }, function() {
            var index = $(this).index() + 1;
            $('.timetable th:nth-child(' + index + ')').removeClass('hover');
        });

        $('.row-act-toggle').click(function() {
            var id = $(this).parents('tr').data('row');
            var $td = $(this).parents('td');
            $.ajax({
                url: Routing.generate('timetable_row_toggle_act', {timetableRow: id}),
                success: function (response) {
                    if (response.has_act) {
                        $td.addClass('has-act');
                    } else {
                        $td.removeClass('has-act');
                    }
                }
            });
        });
    };

    BootstrapTable.prototype.horizontalScroll = function () {
        _horizontalScroll.apply(this, Array.prototype.slice.apply(arguments));

        this.$tableBody.on('scroll', function () {
            localStorage.scrollTo = $(this).scrollTop();
        });

        if (localStorage.scrollTo) {
            this.scrollTo(parseInt(localStorage.scrollTo));
        }
    };

    BootstrapTable.prototype.onColumnSearch = function (event) {
        _onColumnSearch.apply(this, Array.prototype.slice.apply(arguments));

        var filterColumnsDefaults = {};
        this.$fixedHeader.find('.form-control').each(function() {
            var text = $.trim($(this).val());
            var $field = $(this).closest('[data-field]').data('field');


            if (text) {
                filterColumnsDefaults[$field] = text;
            }
        });

        if (filterColumnsDefaults) {
            localStorage.filterColumnsDefaults = JSON.stringify(filterColumnsDefaults);
        } else {
            localStorage.removeItem('filterColumnsDefaults');
        }

    };
})(jQuery);