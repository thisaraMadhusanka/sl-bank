/* global emiManagerAdmin, wp, jQuery */

(function ($) {
    'use strict';

    $(document).ready(function () {

        /**
         * Toast notification helper
         */
        function showToast(message, type = 'success') {
            const $container = $('#emi-toast-container');
            const $toast = $('<div class="emi-toast ' + type + '"><span>' + message + '</span></div>');
            $container.append($toast);
            setTimeout(function () { $toast.addClass('show'); }, 10);
            setTimeout(function () {
                $toast.removeClass('show');
                setTimeout(function () { $toast.remove(); }, 300);
            }, 3000);
        }

        /**
         * Initialize Color Pickers (safely)
         */
        if (typeof $.fn.wpColorPicker === 'function') {
            $('.emi-color-picker').wpColorPicker();
        }

        /**
         * Tabs Logic — Persist active tab via URL hash
         */
        function activateTab(hash) {
            if (!hash || !$(hash).length) return;
            $('#emi-tabs a').removeClass('nav-tab-active');
            $('#emi-tabs a[href="' + hash + '"]').addClass('nav-tab-active');
            $('.emi-tab-pane').removeClass('active');
            $(hash).addClass('active');
        }

        // On page load, check URL hash
        if (window.location.hash) {
            activateTab(window.location.hash);
        }

        $('#emi-tabs a').on('click', function (e) {
            e.preventDefault();
            var hash = $(this).attr('href');
            activateTab(hash);
            // Update URL hash without scrolling
            history.replaceState(null, null, hash);
        });

        /**
         * Media Uploader Logic
         */
        let mediaUploader;
        $(document).on('click', '.emi-upload-btn', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $wrap = $btn.closest('.emi-logo-upload-wrap');
            const $input = $wrap.find('.emi-logo-input');
            const $preview = $wrap.find('.emi-logo-preview');

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Bank Logo',
                button: { text: 'Choose Logo' },
                multiple: false
            });
            mediaUploader.on('select', function () {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $input.val(attachment.url);
                $preview.attr('src', attachment.url).show();
            });
            mediaUploader.open();
        });

        /**
         * Helper: reload but keep the current tab hash
         */
        function reloadKeepTab() {
            var hash = window.location.hash || '#tab-active-banks';
            if (window.location.hash === hash) {
                window.location.reload();
            } else {
                window.location.hash = hash;
                window.location.reload();
            }
        }

        /**
         * Global Settings Save
         */
        $('#emi-save-global').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('terms_html')) {
                tinyMCE.get('terms_html').save();
            }

            const data = $('#emi-global-form').serialize() + '&action=emi_save_global_settings&nonce=' + emiManagerAdmin.nonce;

            $.post(emiManagerAdmin.ajaxUrl, data, function (res) {
                $btn.prop('disabled', false).text('Save Global Settings');
                if (res.success) {
                    showToast(res.data.message);
                } else {
                    showToast(res.data.message || emiManagerAdmin.strings.error, 'error');
                }
            }).fail(function () {
                $btn.prop('disabled', false).text('Save Global Settings');
                showToast(emiManagerAdmin.strings.error, 'error');
            });
        });

        /**
         * Active Banks Accordion
         */
        $(document).on('click', '.emi-bank-accordion-header', function (e) {
            // Don't toggle if user clicked on action buttons inside the header
            if ($(e.target).closest('.emi-bank-actions').length > 0) return;
            const $body = $(this).next('.emi-bank-accordion-body');
            $('.emi-bank-accordion-body').not($body).slideUp();
            $body.slideToggle();
        });
        $(document).on('click', '.emi-toggle-accordion', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $header = $(this).closest('.emi-bank-accordion-header');
            const $body = $header.next('.emi-bank-accordion-body');
            $('.emi-bank-accordion-body').not($body).slideUp();
            $body.slideToggle();
        });

        /**
         * Add/Remove Plans in Table
         */
        $(document).on('click', '.emi-add-plan', function (e) {
            e.preventDefault();
            const $tableBody = $(this).closest('.emi-plans-table-container').find('tbody');
            const template = wp.template('emi-plan-row');
            const nextIndex = $tableBody.find('tr').length;
            $tableBody.append(template({ index: nextIndex }));
        });

        $(document).on('click', '.emi-remove-plan', function (e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });

        /**
         * Save Bank (Edit/Create) — stay on current tab after reload
         */
        $(document).on('click', '.emi-save-bank', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $form = $btn.closest('form');
            const originalText = $btn.text();

            if (!$form.find('input[name="bank_name"]').val()) {
                showToast('Bank Name is required.', 'error');
                return;
            }

            $btn.prop('disabled', true).text('Saving...');
            let postData = $form.serialize() + '&action=emi_save_bank&nonce=' + emiManagerAdmin.nonce;

            $.post(emiManagerAdmin.ajaxUrl, postData, function (res) {
                if (res.success) {
                    showToast(res.data.message);
                    setTimeout(reloadKeepTab, 1000);
                } else {
                    $btn.prop('disabled', false).text(originalText);
                    showToast(res.data.message || emiManagerAdmin.strings.error, 'error');
                }
            }).fail(function () {
                $btn.prop('disabled', false).text(originalText);
                showToast(emiManagerAdmin.strings.error, 'error');
            });
        });

        /**
         * Delete Bank — use stopPropagation so accordion header doesn't swallow the click
         */
        $(document).on('click', '.emi-delete-bank', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const bankId = $(this).data('id');
            const $btn = $(this);

            if (!confirm(emiManagerAdmin.strings.confirmDelete)) return;

            $btn.prop('disabled', true).text('Removing...');

            $.post(emiManagerAdmin.ajaxUrl, {
                action: 'emi_delete_bank',
                bank_id: bankId,
                nonce: emiManagerAdmin.nonce
            }, function (res) {
                if (res.success) {
                    showToast(res.data.message);
                    setTimeout(reloadKeepTab, 1000);
                } else {
                    $btn.prop('disabled', false).text('Remove Bank');
                    showToast(res.data.message || emiManagerAdmin.strings.error, 'error');
                }
            }).fail(function () {
                $btn.prop('disabled', false).text('Remove Bank');
                showToast(emiManagerAdmin.strings.error, 'error');
            });
        });

        /**
         * Preset Multi-Select — Prevent selecting already-added banks
         */
        const selectedPresets = new Set();
        const $bulkApplyBtn = $('#emi-bulk-apply-presets');

        $(document).on('click', '.emi-preset-selectable', function () {
            const $card = $(this);
            // If this bank is already configured, don't allow selection
            if ($card.hasClass('emi-preset-added')) {
                showToast('This bank is already added.', 'error');
                return;
            }

            const presetId = $card.data('preset');
            if ($card.hasClass('selected')) {
                $card.removeClass('selected');
                selectedPresets.delete(presetId);
            } else {
                $card.addClass('selected');
                selectedPresets.add(presetId);
            }

            if (selectedPresets.size > 0) {
                $bulkApplyBtn.prop('disabled', false).text('Apply Selected Banks (' + selectedPresets.size + ')');
            } else {
                $bulkApplyBtn.prop('disabled', true).text('Apply Selected Banks');
            }
        });

        $bulkApplyBtn.on('click', function (e) {
            e.preventDefault();
            if (selectedPresets.size === 0) return;

            const $btn = $(this);
            $btn.prop('disabled', true).text('Adding...');

            let completed = 0;
            let total = selectedPresets.size;
            let successCount = 0;

            selectedPresets.forEach(function (presetId) {
                $.post(emiManagerAdmin.ajaxUrl, {
                    action: 'emi_add_preset_bank',
                    preset: presetId,
                    nonce: emiManagerAdmin.nonce
                }, function (res) {
                    completed++;
                    if (res.success) successCount++;
                    if (completed === total) {
                        showToast('Successfully added ' + successCount + ' bank(s).');
                        setTimeout(reloadKeepTab, 1500);
                    }
                }).fail(function () {
                    completed++;
                    if (completed === total) {
                        showToast('Finished with errors.', 'error');
                        setTimeout(reloadKeepTab, 1500);
                    }
                });
            });
        });

    });
})(jQuery);