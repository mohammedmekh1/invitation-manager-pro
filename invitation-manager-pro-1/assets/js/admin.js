
/**
 * Admin JavaScript for Invitation Manager Pro
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initializeAdmin();
    });

    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        initializeDataTables();
        initializeFormValidation();
        initializeAjaxActions();
        initializeMediaUploader();
        initializeBulkActions();
        initializeConfirmDialogs();
        initializeTooltips();
        initializeCharts();
        initializeEventFilters();
        initializeAutoRefresh();
    }

    /**
     * Initialize DataTables
     */
    function initializeDataTables() {
        if ($.fn.DataTable) {
            $('.impro-data-table').each(function() {
                const $table = $(this);
                const pageLength = $table.data('page-length') || 25;
                const orderColumn = $table.data('order-column') || 0;
                const orderDirection = $table.data('order-direction') || 'asc';
                
                $table.DataTable({
                    responsive: true,
                    pageLength: pageLength,
                    order: [[orderColumn, orderDirection]],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
                        search: "بحث:",
                        lengthMenu: "عرض _MENU_ سجلات لكل صفحة",
                        info: "عرض _START_ إلى _END_ من أصل _TOTAL_ سجل",
                        infoEmpty: "عرض 0 إلى 0 من أصل 0 سجل",
                        infoFiltered: "(مرشحة من مجموع _MAX_ سجل)",
                        loadingRecords: "جار التحميل...",
                        zeroRecords: "لم يُعثر على أية سجلات",
                        emptyTable: "لا توجد بيانات متاحة في الجدول",
                        paginate: {
                            first: "الأول",
                            previous: "السابق",
                            next: "التالي",
                            last: "الأخير"
                        }
                    },
                    columnDefs: [
                        {
                            targets: 'no-sort',
                            orderable: false
                        }
                    ],
                    dom: '<"top"f<"clear">>rt<"bottom"ilp<"clear">>'
                });
            });
        }
    }

    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        // Real-time validation
        $('.impro-form input[required], .impro-form select[required], .impro-form textarea[required]').on('blur', function() {
            validateField($(this));
        });

        // Form submission validation
        $('.impro-form').on('submit', function(e) {
            if (!validateForm($(this))) {
                e.preventDefault();
                showNotification('error', 'يرجى تصحيح الأخطاء في النموذج');
                return false;
            }
        });
    }

    /**
     * Validate individual field
     */
    function validateField($field) {
        const value = $field.val().trim();
        const fieldType = $field.attr('type');
        const isRequired = $field.prop('required');
        let isValid = true;
        let errorMessage = '';

        // Remove existing error
        $field.removeClass('error');
        $field.next('.error-message').remove();

        // Required field validation
        if (isRequired && !value) {
            isValid = false;
            errorMessage = impro_admin.strings.required || 'هذا الحقل مطلوب';
        }

        // Email validation
        if (fieldType === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            errorMessage = 'يرجى إدخال بريد إلكتروني صحيح';
        }

        // Date validation
        if (fieldType === 'date' && value && !isValidDate(value)) {
            isValid = false;
            errorMessage = 'يرجى إدخال تاريخ صحيح';
        }

        // Phone validation
        if ($field.hasClass('phone-field') && value && !isValidPhone(value)) {
            isValid = false;
            errorMessage = 'يرجى إدخال رقم هاتف صحيح';
        }

        // Number validation
        if (fieldType === 'number' && value && !isValidNumber(value)) {
            isValid = false;
            errorMessage = 'يرجى إدخال رقم صحيح';
        }

        // Show error if invalid
        if (!isValid) {
            $field.addClass('error');
            $field.after('<div class="error-message" style="color: #d63638; font-size: 12px; margin-top: 5px;">' + errorMessage + '</div>');
        }

        return isValid;
    }

    /**
     * Validate entire form
     */
    function validateForm($form) {
        let isValid = true;
        
        $form.find('input[required], select[required], textarea[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
                // Focus on first invalid field
                if (isValid) {
                    $(this).focus();
                }
            }
        });

        return isValid;
    }

    /**
     * Initialize AJAX actions
     */
    function initializeAjaxActions() {
        // Send invitation
        $(document).on('click', '.impro-send-invitation', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const invitationId = $button.data('invitation-id');
            
            if (!invitationId) {
                showNotification('error', 'معرف الدعوة غير متوفر');
                return;
            }
            
            sendInvitation(invitationId, $button);
        });

        // Bulk send invitations
        $(document).on('click', '.impro-bulk-send-invitations', function(e) {
            e.preventDefault();
            
            const selectedIds = getSelectedIds('invitation');
            if (selectedIds.length === 0) {
                showNotification('warning', 'يرجى اختيار دعوة واحدة على الأقل');
                return;
            }
            
            if (!confirm(impro_admin.strings.confirm_send || 'هل أنت متأكد من إرسال الدعوات المحددة؟')) {
                return;
            }
            
            bulkSendInvitations(selectedIds);
        });

        // Export data
        $(document).on('click', '.impro-export-data', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const exportType = $button.data('export-type');
            const eventId = $button.data('event-id') || 0;
            
            if (!exportType) {
                showNotification('error', 'نوع التصدير غير محدد');
                return;
            }
            
            exportData(exportType, eventId, $button);
        });

        // Delete item
        $(document).on('click', '.impro-delete-item', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const itemId = $button.data('item-id');
            const itemType = $button.data('item-type');
            
            if (!itemId || !itemType) {
                showNotification('error', 'بيانات الحذف غير مكتملة');
                return;
            }
            
            if (!confirm(impro_admin.strings.confirm_delete || 'هل أنت متأكد من الحذف؟')) {
                return;
            }
            
            deleteItem(itemType, itemId, $button);
        });

        // Reset invitation
        $(document).on('click', '.impro-reset-invitation', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const invitationId = $button.data('invitation-id');
            
            if (!invitationId) {
                showNotification('error', 'معرف الدعوة غير متوفر');
                return;
            }
            
            if (!confirm('هل أنت متأكد من إعادة تعيين هذه الدعوة؟ سيتم إنشاء رمز جديد.')) {
                return;
            }
            
            resetInvitation(invitationId, $button);
        });

        // Generate all invitations
        $(document).on('click', '.impro-generate-all-invitations', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const eventId = $button.data('event-id');
            
            if (!eventId) {
                showNotification('error', 'معرف المناسبة غير متوفر');
                return;
            }
            
            if (!confirm('هل أنت متأكد من إنشاء دعوات لجميع المدعوين غير المدعوين؟')) {
                return;
            }
            
            generateAllInvitations(eventId, $button);
        });
    }

    /**
     * Send single invitation
     */
    function sendInvitation(invitationId, $button) {
        const originalText = $button.text();
        const originalClass = $button.attr('class');
        
        $button.prop('disabled', true).text(impro_admin.strings.sending || 'جاري الإرسال...');
        
        $.ajax({
            url: impro_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'impro_send_invitation',
                invitation_id: invitationId,
                nonce: impro_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data || 'تم إرسال الدعوة بنجاح');
                    $button.text(impro_admin.strings.sent || 'تم الإرسال')
                           .removeClass('button-primary')
                           .addClass('button-secondary')
                           .prop('disabled', true);
                    
                    // Update status in table
                    const $row = $button.closest('tr');
                    $row.find('.column-status .impro-status-badge')
                       .removeClass('status-pending')
                       .addClass('status-sent')
                       .text('مرسلة');
                } else {
                    showNotification('error', response.data || impro_admin.strings.error || 'فشل في إرسال الدعوة');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                showNotification('error', impro_admin.strings.error || 'حدث خطأ: ' + error);
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Bulk send invitations
     */
    function bulkSendInvitations(invitationIds) {
        const $button = $('.impro-bulk-send-invitations');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text(impro_admin.strings.sending || 'جاري الإرسال...');
        
        $.ajax({
            url: impro_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'impro_bulk_send_invitations',
                invitation_ids: invitationIds,
                nonce: impro_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message || 'تم إرسال الدعوات بنجاح');
                    // Update button states for sent invitations
                    invitationIds.forEach(function(id) {
                        const $btn = $('.impro-send-invitation[data-invitation-id="' + id + '"]');
                        $btn.text(impro_admin.strings.sent || 'تم الإرسال')
                           .removeClass('button-primary')
                           .addClass('button-secondary')
                           .prop('disabled', true);
                        
                        // Update status in table
                        const $row = $btn.closest('tr');
                        $row.find('.column-status .impro-status-badge')
                           .removeClass('status-pending')
                           .addClass('status-sent')
                           .text('مرسلة');
                    });
                } else {
                    showNotification('error', response.data || impro_admin.strings.error || 'فشل في إرسال الدعوات');
                }
                $button.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                showNotification('error', impro_admin.strings.error || 'حدث خطأ: ' + error);
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Export data
     */
    function exportData(type, eventId, $button) {
        const originalText = $button.text();
        
        $button.prop('disabled', true).text(impro_admin.strings.exporting || 'جاري التصدير...');
        
        $.ajax({
            url: impro_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'impro_export_data',
                type: type,
                event_id: eventId,
                nonce: impro_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.url) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = 'export_' + type + '_' + new Date().getTime() + '.csv';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('success', 'تم تصدير البيانات بنجاح');
                } else {
                    showNotification('error', response.data || 'فشل في تصدير البيانات');
                }
                $button.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                showNotification('error', 'حدث خطأ أثناء التصدير: ' + error);
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Delete item
     */
    function deleteItem(type, itemId, $button) {
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                impro_action: 'delete_' + type,
                [type + '_id']: itemId,
                _wpnonce: $('input[name="_wpnonce"]').val()
            },
            success: function(response) {
                // Remove row from table
                $button.closest('tr').fadeOut(function() {
                    $(this).remove();
                    showNotification('success', 'تم الحذف بنجاح');
                    
                    // Update statistics if on dashboard
                    if ($('#impro-stats-dashboard').length) {
                        location.reload();
                    }
                });
            },
            error: function(xhr, status, error) {
                showNotification('error', 'فشل في الحذف: ' + error);
            }
        });
    }

    /**
     * Reset invitation
     */
    function resetInvitation(invitationId, $button) {
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                impro_action: 'reset_invitation',
                invitation_id: invitationId,
                _wpnonce: $('input[name="_wpnonce"]').val()
            },
            success: function(response) {
                showNotification('success', 'تم إعادة تعيين الدعوة بنجاح');
                // Reload the page to show updated data
                location.reload();
            },
            error: function(xhr, status, error) {
                showNotification('error', 'فشل في إعادة تعيين الدعوة: ' + error);
            }
        });
    }

    /**
     * Generate all invitations
     */
    function generateAllInvitations(eventId, $button) {
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                impro_action: 'generate_all_invitations',
                event_id: eventId,
                _wpnonce: $('input[name="_wpnonce"]').val()
            },
            success: function(response) {
                showNotification('success', 'تم إنشاء الدعوات بنجاح');
                // Reload the page to show updated data
                location.reload();
            },
            error: function(xhr, status, error) {
                showNotification('error', 'فشل في إنشاء الدعوات: ' + error);
            }
        });
    }

    /**
     * Initialize media uploader
     */
    function initializeMediaUploader() {
        $('.impro-upload-image-button').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $input = $button.siblings('input[type="url"]');
            const $preview = $button.siblings('.impro-image-preview');
            
            // Check if wp.media is available
            if (typeof wp === 'undefined' || !wp.media) {
                showNotification('error', 'مكتبة الوسائط غير متوفرة');
                return;
            }
            
            const mediaUploader = wp.media({
                title: 'اختر صورة',
                button: {
                    text: 'استخدام هذه الصورة'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $input.val(attachment.url).trigger('change');
                
                if ($preview.length) {
                    $preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px;">');
                }
            });
            
            mediaUploader.open();
        });
    }

    /**
     * Initialize bulk actions
     */
    function initializeBulkActions() {
        // Select all checkbox
        $(document).on('change', '.impro-select-all', function() {
            const $this = $(this);
            const targetClass = $this.data('target') || '.impro-bulk-select';
            $(targetClass).prop('checked', $this.prop('checked'));
            updateBulkActionButtons();
        });

        // Individual checkboxes
        $(document).on('change', '.impro-bulk-select', function() {
            updateBulkActionButtons();
            
            // Update select all checkbox
            const $selectAll = $('.impro-select-all');
            const totalCheckboxes = $('.impro-bulk-select').length;
            const checkedCheckboxes = $('.impro-bulk-select:checked').length;
            
            $selectAll.prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
        });
    }

    /**
     * Update bulk action buttons state
     */
    function updateBulkActionButtons() {
        const selectedCount = $('.impro-bulk-select:checked').length;
        $('.impro-bulk-action-button').prop('disabled', selectedCount === 0);
        
        if (selectedCount > 0) {
            $('.impro-selected-count').text(selectedCount);
            $('.impro-bulk-actions').show();
        } else {
            $('.impro-bulk-actions').hide();
        }
    }

    /**
     * Get selected item IDs
     */
    function getSelectedIds(type = 'item') {
        const ids = [];
        $('.impro-bulk-select:checked').each(function() {
            const id = $(this).val();
            if (id && !isNaN(id)) {
                ids.push(parseInt(id));
            }
        });
        return ids;
    }

    /**
     * Initialize confirm dialogs
     */
    function initializeConfirmDialogs() {
        $('.impro-confirm-action').on('click', function(e) {
            const message = $(this).data('confirm-message') || impro_admin.strings.confirm_delete || 'هل أنت متأكد؟';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        // Simple CSS tooltips
        $(document).on('mouseenter', '[data-tooltip]', function() {
            const tooltipText = $(this).data('tooltip');
            if (tooltipText) {
                $(this).attr('title', tooltipText);
            }
        });
        
        // If jQuery UI tooltip is available
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip({
                content: function() {
                    return $(this).data('tooltip');
                },
                position: {
                    my: "center bottom-20",
                    at: "center top"
                }
            });
        }
    }

    /**
     * Initialize charts
     */
    function initializeCharts() {
        if (typeof Chart !== 'undefined') {
            initializeStatisticsCharts();
        } else {
            // Fallback for when Chart.js is not loaded
            console.warn('Chart.js not loaded, charts will not be displayed');
        }
    }

    /**
     * Initialize statistics charts
     */
    function initializeStatisticsCharts() {
        // RSVP Status Chart
        const rsvpCanvas = document.getElementById('rsvp-status-chart');
        if (rsvpCanvas && rsvpCanvas.dataset) {
            const ctx = rsvpCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['موافق', 'معتذر', 'في الانتظار'],
                    datasets: [{
                        data: [
                            parseInt(rsvpCanvas.dataset.accepted || 0),
                            parseInt(rsvpCanvas.dataset.declined || 0),
                            parseInt(rsvpCanvas.dataset.pending || 0)
                        ],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((context.raw / total) * 100);
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Invitation Status Chart
        const invitationCanvas = document.getElementById('invitation-status-chart');
        if (invitationCanvas && invitationCanvas.dataset) {
            const ctx = invitationCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['مرسلة', 'مفتوحة', 'في الانتظار'],
                    datasets: [{
                        label: 'عدد الدعوات',
                        data: [
                            parseInt(invitationCanvas.dataset.sent || 0),
                            parseInt(invitationCanvas.dataset.opened || 0),
                            parseInt(invitationCanvas.dataset.pending || 0)
                        ],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Event Statistics Chart
        const eventStatsCanvas = document.getElementById('event-stats-chart');
        if (eventStatsCanvas && eventStatsCanvas.dataset) {
            const ctx = eventStatsCanvas.getContext('2d');
            const labels = JSON.parse(eventStatsCanvas.dataset.labels || '[]');
            const data = JSON.parse(eventStatsCanvas.dataset.data || '[]');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'المناسبات',
                        data: data,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    /**
     * Initialize event filters
     */
    function initializeEventFilters() {
        $('.impro-filter-form select').on('change', function() {
            $(this).closest('form').submit();
        });
    }

    /**
     * Initialize auto-refresh
     */
    function initializeAutoRefresh() {
        // Auto-refresh for dashboard statistics
        if ($('.impro-auto-refresh').length) {
            setInterval(function() {
                if (document.hidden) return; // Don't refresh when tab is not active
                
                $('.impro-auto-refresh').each(function() {
                    const $element = $(this);
                    const refreshUrl = $element.data('refresh-url');
                    const refreshInterval = $element.data('refresh-interval') || 30000; // 30 seconds default
                    
                    if (refreshUrl) {
                        $.get(refreshUrl, function(data) {
                            $element.html(data);
                        });
                    }
                });
            }, 60000); // Check every minute
        }
    }

    /**
     * Show notification
     */
    function showNotification(type, message) {
        // Remove existing notifications of same type
        $('.impro-notification-' + type).remove();
        
        const notificationId = 'impro-notification-' + Date.now();
        const notification = $(`
            <div id="${notificationId}" class="impro-notification impro-notification-${type}">
                <div class="impro-notification-content">${message}</div>
                <button type="button" class="impro-notification-close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto hide after 5 seconds
        setTimeout(function() {
            $('#' + notificationId).fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Close button handler
        notification.find('.impro-notification-close').on('click', function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Utility functions
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }

    function isValidPhone(phone) {
        // Simple phone validation - can be enhanced
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,20}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    function isValidNumber(number) {
        return !isNaN(parseFloat(number)) && isFinite(number);
    }

    /**
     * Add notification styles
     */
    function addNotificationStyles() {
        if ($('#impro-notification-styles').length) return;
        
        const styles = `
            <style id="impro-notification-styles">
                .impro-notification {
                    position: fixed;
                    top: 32px;
                    right: 20px;
                    padding: 15px 20px 15px 40px;
                    border-radius: 6px;
                    color: white;
                    font-weight: 500;
                    z-index: 999999;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    min-width: 300px;
                    max-width: 500px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .impro-notification-success { 
                    background: linear-gradient(135deg, #10b981, #059669); 
                }
                .impro-notification-error { 
                    background: linear-gradient(135deg, #ef4444, #dc2626); 
                }
                .impro-notification-warning { 
                    background: linear-gradient(135deg, #f59e0b, #d97706); 
                }
                .impro-notification-info { 
                    background: linear-gradient(135deg, #3b82f6, #2563eb); 
                }
                .impro-notification-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 20px;
                    cursor: pointer;
                    padding: 0;
                    margin: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .impro-notification-close:hover {
                    opacity: 0.8;
                }
                .error {
                    border-color: #d63638 !important;
                    box-shadow: 0 0 0 1px #d63638 !important;
                }
                .image-preview img {
                    margin-top: 10px;
                }
                .impro-bulk-actions {
                    margin: 15px 0;
                    padding: 15px;
                    background: #f6f7f7;
                    border: 1px solid #dcdcde;
                    border-radius: 4px;
                }
            </style>
        `;
        $('head').append(styles);
    }

    // Add notification styles when DOM is ready
    $(document).ready(function() {
        addNotificationStyles();
    });

})(jQuery);