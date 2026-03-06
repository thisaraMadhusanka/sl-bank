(function ($) {
    'use strict';

    var EmiFrontend = {
        debounceTimer: null,
        debounceDelay: 300,

        init: function () {
            this.bindEvents();
            this.bindVariationEvents();
        },

        bindEvents: function () {
            var $container = $('#emi-section');
            
            // Card Click Event
            $container.on('click', '.emi-bank-card', function (e) {
                e.preventDefault();
                var $card = $(this);
                var bankId = $card.data('bank-id');
                var $panel = $('#emi-panel-' + bankId);
                var isExpanded = $card.attr('aria-expanded') === 'true';

                // Close all cards and panels
                $('.emi-bank-card').attr('aria-expanded', 'false');
                $('.emi-panel').attr('hidden', true);

                if (!isExpanded) {
                    // Open the clicked card's panel
                    $card.attr('aria-expanded', 'true');
                    $panel.removeAttr('hidden');
                }
            });

            // Close button click event
            $container.on('click', '.emi-panel__close', function (e) {
                e.preventDefault();
                $('.emi-bank-card').attr('aria-expanded', 'false');
                $('.emi-panel').attr('hidden', true);
            });
        },

        bindVariationEvents: function () {
            if (!emiManagerFrontend.isVariable) { return; }
            var self = this;
            $(document).on('found_variation', '.variations_form', function (event, variation) {
                if (variation && typeof variation.display_price !== 'undefined') {
                    self.debouncedUpdate(variation.display_price);
                }
            });
            $(document).on('reset_data', '.variations_form', function () {
                self.debouncedUpdate(parseFloat(emiManagerFrontend.initialPrice) || 0);
            });
        },

        debouncedUpdate: function (price) {
            var self = this;
            if (this.debounceTimer) { clearTimeout(this.debounceTimer); }
            this.debounceTimer = setTimeout(function () {
                self.updateEmi(price);
            }, this.debounceDelay);
        },

        updateEmi: function (price) {
            if (price <= 0) {
                $('#emi-section').hide();
                return;
            } else {
                $('#emi-section').show();
            }

            var self = this;
            var $section = $('#emi-section');
            $section.addClass('emi-section--loading');

            var variationId = $('form.variations_form input[name="variation_id"]').val();
            
            $.ajax({
                url: emiManagerFrontend.restUrl + 'product/' + emiManagerFrontend.productId,
                method: 'GET',
                data: { variation: parseInt(variationId) || 0 },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', emiManagerFrontend.restNonce);
                },
                success: function (response) {
                    if (response && response.banks) {
                        self.renderResponse(response.banks);
                    }
                },
                error: function () {
                    self.clientSideRecalculate(price);
                },
                complete: function () {
                    $section.removeClass('emi-section--loading');
                }
            });
        },

        renderResponse: function (banks) {
            $.each(banks, function (i, bank) {
                var $card = $('.emi-bank-card[data-bank-id="' + bank.id + '"]');
                var $panel = $('#emi-panel-' + bank.id);
                
                if (!$card.length || !$panel.length || !bank.plans || !bank.plans.length) { return; }

                var lowest = Infinity;
                var tbodyHtml = '';
                
                $.each(bank.plans, function (j, plan) {
                    if (plan.monthly < lowest) { lowest = plan.monthly; }
                    tbodyHtml += '<tr>' +
                        '<td>' + plan.months + '</td>' +
                        '<td>' + EmiFrontend.formatNumber(plan.fee_pct) + '%</td>' +
                        '<td class="emi-table__monthly">Rs. <span class="emi-amount">' + EmiFrontend.formatNumberNoDecimals(plan.monthly) + '</span></td>' +
                        '<td class="emi-table__total">Rs. <span class="emi-amount">' + EmiFrontend.formatNumberNoDecimals(plan.total) + '</span></td>' +
                        '</tr>';
                });

                if (lowest < Infinity) {
                    $card.find('.emi-preview-amount').text(EmiFrontend.formatNumberNoDecimals(lowest));
                }
                
                $panel.find('tbody').html(tbodyHtml);
            });
        },

        clientSideRecalculate: function (price) {
            $('.emi-panel').each(function () {
                var $panel = $(this);
                var bankId = $panel.attr('id').replace('emi-panel-', '');
                var $card = $('.emi-bank-card[data-bank-id="' + bankId + '"]');
                var lowest = Infinity;
                
                $panel.find('tbody tr').each(function () {
                    var $row = $(this);
                    var $cells = $row.find('td');
                    var months = parseInt($cells.eq(0).text());
                    var feePercent = parseFloat($cells.eq(1).text());
                    
                    if (!months || months < 1) { return; }
                    
                    var total = price + (price * feePercent / 100);
                    var monthly = total / months;
                    
                    if (monthly < lowest) { lowest = monthly; }
                    
                    $cells.eq(2).find('.emi-amount').text(EmiFrontend.formatNumberNoDecimals(monthly));
                    $cells.eq(3).find('.emi-amount').text(EmiFrontend.formatNumberNoDecimals(total));
                });
                
                if (lowest < Infinity && $card.length) {
                    $card.find('.emi-preview-amount').text(EmiFrontend.formatNumberNoDecimals(lowest));
                }
            });
        },

        formatNumber: function (num) {
            return parseFloat(num).toFixed(2);
        },
        
        formatNumberNoDecimals: function (num) {
            return Math.round(parseFloat(num)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    };

    $(function () {
        if ($('#emi-section').length) {
            EmiFrontend.init();
        }
    });

})(jQuery);