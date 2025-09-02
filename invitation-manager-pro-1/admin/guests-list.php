
<?php
/**
 * Guests list page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة المدير بأمان
try {
    $guest_manager = new IMPRO_Guest_Manager();
    $guests = $guest_manager->get_guests() ?: array();
} catch ( Exception $e ) {
    error_log( 'Failed to load guests: ' . $e->getMessage() );
    $guests = array();
    $guest_manager = null;
}

// التحقق من صحة البيانات
if ( ! is_array( $guests ) ) {
    $guests = array();
}

// تعريف الفئات مع دعم الفلاتر
$guest_categories = apply_filters( 'impro_guest_categories', array(
    'family' => __( 'عائلة', 'invitation-manager-pro' ),
    'friends' => __( 'أصدقاء', 'invitation-manager-pro' ),
    'colleagues' => __( 'زملاء', 'invitation-manager-pro' ),
    'vip' => __( 'شخصيات مهمة', 'invitation-manager-pro' ),
    'other' => __( 'أخرى', 'invitation-manager-pro' )
) );

$category_labels = array(
    'family' => __( 'عائلة', 'invitation-manager-pro' ),
    'friends' => __( 'أصدقاء', 'invitation-manager-pro' ),
    'colleagues' => __( 'زملاء', 'invitation-manager-pro' ),
    'vip' => __( 'شخصيات مهمة', 'invitation-manager-pro' ),
    'other' => __( 'أخرى', 'invitation-manager-pro' )
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'المدعوين', 'invitation-manager-pro' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=add' ) ); ?>" class="page-title-action">
        <?php _e( 'إضافة مدعو جديد', 'invitation-manager-pro' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=import' ) ); ?>" class="page-title-action">
        <?php _e( 'استيراد مدعوين', 'invitation-manager-pro' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'created' => __( 'تم إنشاء المدعو بنجاح.', 'invitation-manager-pro' ),
            'updated' => __( 'تم تحديث المدعو بنجاح.', 'invitation-manager-pro' ),
            'deleted' => __( 'تم حذف المدعو بنجاح.', 'invitation-manager-pro' ),
            'imported' => sprintf( __( 'تم استيراد %d مدعو بنجاح.', 'invitation-manager-pro' ), isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 0 ),
            'bulk_deleted' => sprintf( __( 'تم حذف %d مدعو بنجاح.', 'invitation-manager-pro' ), isset( $_GET['deleted'] ) ? intval( $_GET['deleted'] ) : 0 ),
            'bulk_updated' => sprintf( __( 'تم تحديث %d مدعو بنجاح.', 'invitation-manager-pro' ), isset( $_GET['updated'] ) ? intval( $_GET['updated'] ) : 0 )
        );
        
        $message_key = sanitize_text_field( $_GET['message'] );
        $message_text = isset( $success_messages[ $message_key ] ) ? $success_messages[ $message_key ] : '';
        
        if ( ! empty( $message_text ) ) :
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $message_text ); ?></p>
        </div>
    <?php 
        endif;
    endif; 
    ?>

    <?php 
    // عرض رسائل الخطأ
    if ( isset( $_GET['error'] ) ) : 
        $error_messages = array(
            'create_failed' => __( 'فشل في إنشاء المدعو.', 'invitation-manager-pro' ),
            'update_failed' => __( 'فشل في تحديث المدعو.', 'invitation-manager-pro' ),
            'delete_failed' => __( 'فشل في حذف المدعو.', 'invitation-manager-pro' ),
            'import_failed' => __( 'فشل في استيراد المدعوين.', 'invitation-manager-pro' ),
            'bulk_delete_failed' => __( 'فشل في حذف المدعوين المحددين.', 'invitation-manager-pro' ),
            'bulk_update_failed' => __( 'فشل في تحديث المدعوين المحددين.', 'invitation-manager-pro' ),
            'permission_denied' => __( 'ليس لديك الصلاحية للقيام بهذا الإجراء.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_text = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : __( 'حدث خطأ غير متوقع.', 'invitation-manager-pro' );
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_text ); ?></p>
        </div>
    <?php endif; ?>

    <!-- أدوات التصفية والبحث -->
    <div class="impro-guests-toolbar">
        <div class="impro-search-box">
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                <input type="hidden" name="page" value="impro-guests">
                <input 
                    type="search" 
                    name="s" 
                    value="<?php echo isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : ''; ?>" 
                    placeholder="<?php esc_attr_e( 'البحث في المدعوين...', 'invitation-manager-pro' ); ?>"
                    class="impro-search-input"
                >
                <button type="submit" class="button"><?php _e( 'بحث', 'invitation-manager-pro' ); ?></button>
                <?php if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests' ) ); ?>" class="button button-secondary">
                        <?php _e( 'مسح البحث', 'invitation-manager-pro' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="impro-filter-options">
            <select name="category" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests' ) ); ?>">
                    <?php _e( 'جميع الفئات', 'invitation-manager-pro' ); ?>
                </option>
                <?php foreach ( $guest_categories as $key => $label ) : ?>
                    <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&category=' . $key ) ); ?>" 
                            <?php echo isset( $_GET['category'] ) && $_GET['category'] === $key ? 'selected' : ''; ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="plus_one" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests' ) ); ?>">
                    <?php _e( 'جميع الحالات', 'invitation-manager-pro' ); ?>
                </option>
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&plus_one=1' ) ); ?>" 
                        <?php echo isset( $_GET['plus_one'] ) && $_GET['plus_one'] === '1' ? 'selected' : ''; ?>>
                    <?php _e( 'مسموح بمرافق', 'invitation-manager-pro' ); ?>
                </option>
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&plus_one=0' ) ); ?>" 
                        <?php echo isset( $_GET['plus_one'] ) && $_GET['plus_one'] === '0' ? 'selected' : ''; ?>>
                    <?php _e( 'غير مسموح بمرافق', 'invitation-manager-pro' ); ?>
                </option>
            </select>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <?php if ( ! empty( $guests ) ) : ?>
        <div class="impro-guests-summary">
            <div class="summary-card">
                <span class="summary-number"><?php echo esc_html( count( $guests ) ); ?></span>
                <span class="summary-label"><?php _e( 'إجمالي المدعوين', 'invitation-manager-pro' ); ?></span>
            </div>
            <?php
            $plus_one_count = 0;
            $category_counts = array();
            foreach ( $guests as $guest ) {
                if ( isset( $guest->plus_one_allowed ) && $guest->plus_one_allowed ) {
                    $plus_one_count++;
                }
                if ( isset( $guest->category ) ) {
                    if ( ! isset( $category_counts[ $guest->category ] ) ) {
                        $category_counts[ $guest->category ] = 0;
                    }
                    $category_counts[ $guest->category ]++;
                }
            }
            ?>
            <div class="summary-card">
                <span class="summary-number"><?php echo esc_html( $plus_one_count ); ?></span>
                <span class="summary-label"><?php _e( 'مسموح بمرافق', 'invitation-manager-pro' ); ?></span>
            </div>
            <div class="summary-card">
                <span class="summary-number"><?php echo esc_html( count( $category_counts ) ); ?></span>
                <span class="summary-label"><?php _e( 'فئات مختلفة', 'invitation-manager-pro' ); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- أزرار الإجراءات الجماعية -->
    <?php if ( ! empty( $guests ) ) : ?>
        <div class="impro-bulk-actions">
            <form method="post" id="bulk-action-form">
                <input type="hidden" name="impro_action" value="bulk_guest_action">
                <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
                
                <select name="bulk_action" id="bulk-action-selector">
                    <option value=""><?php _e( 'الإجراءات الجماعية', 'invitation-manager-pro' ); ?></option>
                    <option value="delete"><?php _e( 'حذف المحدد', 'invitation-manager-pro' ); ?></option>
                    <option value="allow_plus_one"><?php _e( 'السماح بمرافق', 'invitation-manager-pro' ); ?></option>
                    <option value="disallow_plus_one"><?php _e( 'منع المرافق', 'invitation-manager-pro' ); ?></option>
                    <option value="export"><?php _e( 'تصدير المحدد', 'invitation-manager-pro' ); ?></option>
                </select>
                <button type="button" id="bulk-action-button" class="button" disabled>
                    <?php _e( 'تطبيق', 'invitation-manager-pro' ); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- جدول المدعوين -->
    <div class="impro-guests-table-container">
        <table class="wp-list-table widefat fixed striped improvments-table">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </th>
                    <th class="manage-column column-primary"><?php _e( 'الاسم', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'البريد الإلكتروني', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'الهاتف', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'الفئة', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'مرافق مسموح به', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'الدعوات', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column column-actions"><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $guests ) ) : ?>
                    <?php foreach ( $guests as $guest ) : ?>
                        <?php
                        // تهيئة البيانات بأمان
                        $guest_id = isset( $guest->id ) ? intval( $guest->id ) : 0;
                        $guest_name = isset( $guest->name ) ? esc_html( $guest->name ) : __( 'غير محدد', 'invitation-manager-pro' );
                        $guest_email = isset( $guest->email ) ? esc_html( $guest->email ) : __( 'غير محدد', 'invitation-manager-pro' );
                        $guest_phone = isset( $guest->phone ) ? esc_html( $guest->phone ) : __( 'غير محدد', 'invitation-manager-pro' );
                        $guest_category = isset( $guest->category ) ? esc_html( $guest->category ) : __( 'غير محدد', 'invitation-manager-pro' );
                        $plus_one_allowed = isset( $guest->plus_one_allowed ) ? (bool) $guest->plus_one_allowed : false;
                        
                        // الحصول على تسمية الفئة
                        $category_label = isset( $category_labels[ $guest_category ] ) ? $category_labels[ $guest_category ] : $guest_category;
                        
                        // الحصول على إحصائيات الدعوات
                        try {
                            $invitation_manager = new IMPRO_Invitation_Manager();
                            $guest_invitations = $invitation_manager->get_guest_invitations( $guest_id ) ?: array();
                            $invitation_count = count( $guest_invitations );
                        } catch ( Exception $e ) {
                            error_log( 'Failed to get guest invitations: ' . $e->getMessage() );
                            $invitation_count = 0;
                        }
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input id="cb-select-<?php echo esc_attr( $guest_id ); ?>" 
                                       type="checkbox" 
                                       name="guest_ids[]" 
                                       value="<?php echo esc_attr( $guest_id ); ?>">
                            </th>
                            <td class="column-primary" data-colname="<?php esc_attr_e( 'الاسم', 'invitation-manager-pro' ); ?>">
                                <strong>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=view&guest_id=' . $guest_id ) ); ?>">
                                        <?php echo $guest_name; ?>
                                    </a>
                                </strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'عرض المزيد', 'invitation-manager-pro' ); ?></span></button>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'البريد الإلكتروني', 'invitation-manager-pro' ); ?>">
                                <?php if ( ! empty( $guest_email ) && $guest_email !== __( 'غير محدد', 'invitation-manager-pro' ) ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $guest_email ); ?>"><?php echo $guest_email; ?></a>
                                <?php else : ?>
                                    <?php echo $guest_email; ?>
                                <?php endif; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'الهاتف', 'invitation-manager-pro' ); ?>">
                                <?php if ( ! empty( $guest_phone ) && $guest_phone !== __( 'غير محدد', 'invitation-manager-pro' ) ) : ?>
                                    <a href="tel:<?php echo esc_attr( $guest_phone ); ?>"><?php echo $guest_phone; ?></a>
                                <?php else : ?>
                                    <?php echo $guest_phone; ?>
                                <?php endif; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'الفئة', 'invitation-manager-pro' ); ?>">
                                <span class="impro-category-badge category-<?php echo esc_attr( $guest_category ); ?>">
                                    <?php echo esc_html( $category_label ); ?>
                                </span>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'مرافق مسموح به', 'invitation-manager-pro' ); ?>">
                                <?php if ( $plus_one_allowed ) : ?>
                                    <span class="impro-status-badge status-accepted"><?php _e( 'نعم', 'invitation-manager-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="impro-status-badge status-declined"><?php _e( 'لا', 'invitation-manager-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'الدعوات', 'invitation-manager-pro' ); ?>">
                                <span class="impro-stat-badge"><?php echo esc_html( $invitation_count ); ?></span>
                            </td>
                            <td class="column-actions" data-colname="<?php esc_attr_e( 'الإجراءات', 'invitation-manager-pro' ); ?>">
                                <div class="impro-action-buttons">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=edit&guest_id=' . $guest_id ) ); ?>" class="button button-small">
                                        <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=view&guest_id=' . $guest_id ) ); ?>" class="button button-small button-secondary">
                                        <?php _e( 'عرض', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=send&guest_id=' . $guest_id ) ); ?>" class="button button-small button-secondary">
                                        <?php _e( 'إرسال دعوة', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <div class="impro-dropdown">
                                        <button type="button" class="button button-small dropdown-toggle">
                                            <?php _e( 'المزيد', 'invitation-manager-pro' ); ?>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=add&guest_id=' . $guest_id ) ); ?>">
                                                <?php _e( 'إضافة رد حضور', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&guest_id=' . $guest_id ) ); ?>">
                                                <?php _e( 'عرض الدعوات', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&guest_id=' . $guest_id ) ); ?>">
                                                <?php _e( 'عرض الردود', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form method="post" style="display:block; margin:0;" 
                                                  onsubmit="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذا المدعو؟', 'invitation-manager-pro' ) ); ?>');">
                                                <input type="hidden" name="impro_action" value="delete_guest">
                                                <input type="hidden" name="guest_id" value="<?php echo esc_attr( $guest_id ); ?>">
                                                <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <?php _e( 'حذف', 'invitation-manager-pro' ); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" class="no-items">
                            <div class="impro-empty-state">
                                <span class="dashicons dashicons-groups"></span>
                                <h3><?php _e( 'لا يوجد مدعوين', 'invitation-manager-pro' ); ?></h3>
                                <p><?php _e( 'لم تقم بإضافة أي مدعوين حتى الآن.', 'invitation-manager-pro' ); ?></p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=add' ) ); ?>" class="button button-primary">
                                    <?php _e( 'إضافة مدعو أول', 'invitation-manager-pro' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=import' ) ); ?>" class="button">
                                    <?php _e( 'استيراد مدعوين', 'invitation-manager-pro' ); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ترقيم الصفحات -->
    <?php if ( ! empty( $guests ) && count( $guests ) > 20 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf( __( 'عرض %d من أصل %d مدعو', 'invitation-manager-pro' ), count( $guests ), count( $guests ) ); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

/* شريط الأدوات */
.impro-guests-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    flex-wrap: wrap;
    gap: 15px;
}

.impro-search-box {
    display: flex;
    gap: 10px;
    flex: 1;
    max-width: 500px;
}

.impro-search-input {
    flex: 1;
    min-width: 200px;
}

.impro-filter-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.impro-filter-options select {
    min-width: 150px;
}

/* إحصائيات سريعة */
.impro-guests-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.summary-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #1d2327;
    margin-bottom: 5px;
}

.summary-label {
    color: #646970;
    font-size: 0.9em;
}

/* أزرار الإجراءات الجماعية */
.impro-bulk-actions {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

#bulk-action-selector {
    margin-left: 10px;
}

#bulk-action-button {
    margin: 0;
}

/* جدول المدعوين */
.impro-guests-table-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.improvments-table {
    border: none;
    margin: 0;
}

.improvments-table thead th {
    background: #f6f7f7;
    border-bottom: 2px solid #ccd0d4;
    font-weight: 600;
}

.improvments-table tbody td {
    vertical-align: middle;
}

/* شارات الفئات */
.impro-category-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}

.category-family {
    background: #e6f4ea;
    color: #008a20;
    border: 1px solid #b3e0c4;
}

.category-friends {
    background: #e1f0fa;
    color: #0073aa;
    border: 1px solid #b3d9f0;
}

.category-colleagues {
    background: #fef8ee;
    color: #d63638;
    border: 1px solid #f8e2df;
}

.category-vip {
    background: #f8e2df;
    color: #d63638;
    border: 1px solid #f0b3b3;
}

.category-other {
    background: #f0f0f1;
    color: #50575e;
    border: 1px solid #dcdcde;
}

/* شارات الحالة */
.impro-status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}

.status-accepted {
    background: #e6f4ea;
    color: #008a20;
    border: 1px solid #b3e0c4;
}

.status-declined {
    background: #f8e2df;
    color: #d63638;
    border: 1px solid #f0b3b3;
}

/* شارات الإحصائيات */
.impro-stat-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f0f0f1;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

/* أزرار الإجراءات */
.impro-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    align-items: center;
}

.impro-action-buttons .button {
    margin: 0;
    font-size: 12px;
    padding: 4px 10px;
}

/* القائمة المنسدلة */
.impro-dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    background: #f6f7f7;
    border: 1px solid #8c8f94;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: right;
    list-style: none;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.175);
}

.impro-dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 8px 12px;
    clear: both;
    font-weight: 400;
    color: #333;
    text-align: inherit;
    white-space: nowrap;
    background: none;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
}

.dropdown-item:hover {
    color: #262626;
    text-decoration: none;
    background-color: #f5f5f5;
}

.dropdown-divider {
    height: 1px;
    margin: 9px 0;
    overflow: hidden;
    background-color: #e5e5e5;
}

.text-danger {
    color: #d63638;
}

/* حالة فارغة */
.impro-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.impro-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #dcdcde;
    margin-bottom: 15px;
}

.impro-empty-state h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 20px;
}

.impro-empty-state p {
    margin: 0 0 20px 0;
    color: #646970;
    font-size: 14px;
}

/* تصميم متجاوب */
@media (max-width: 782px) {
    .impro-guests-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-search-box {
        max-width: none;
    }
    
    .improvments-table .column-primary {
        width: auto;
    }
    
    .improvments-table .column-actions {
        width: 150px;
    }
    
    .impro-action-buttons {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .impro-dropdown {
        position: static;
    }
    
    .dropdown-menu {
        position: static;
        display: block;
        box-shadow: none;
        border: none;
        background: transparent;
        margin-top: 10px;
    }
    
    .dropdown-item {
        padding: 5px 0;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .impro-guests-summary {
        grid-template-columns: 1fr;
    }
    
    .impro-filter-options {
        flex-direction: column;
    }
    
    .impro-filter-options select {
        width: 100%;
    }
    
    .impro-bulk-actions {
        text-align: center;
    }
    
    #bulk-action-selector,
    #bulk-action-button {
        width: 100%;
        margin: 5px 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تحديد الكل / إلغاء التحديد
    $('#cb-select-all-1').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="guest_ids[]"]').prop('checked', isChecked);
        $('#bulk-action-button').prop('disabled', !isChecked);
    });
    
    // تحديث حالة زر الإجراءات الجماعية
    $('input[name="guest_ids[]"]').on('change', function() {
        var checkedCount = $('input[name="guest_ids[]"]:checked').length;
        $('#bulk-action-button').prop('disabled', checkedCount === 0);
        
        // تحديث حالة زر التحديد العام
        var totalCount = $('input[name="guest_ids[]"]').length;
        $('#cb-select-all-1').prop('checked', checkedCount === totalCount && totalCount > 0);
    });
    
    // تنفيذ الإجراءات الجماعية
    $('#bulk-action-button').on('click', function() {
        var action = $('#bulk-action-selector').val();
        var selectedGuests = $('input[name="guest_ids[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedGuests.length === 0) {
            alert('<?php esc_js_e( 'يرجى تحديد مدعوين أولاً', 'invitation-manager-pro' ); ?>');
            return;
        }
        
        if (!action) {
            alert('<?php esc_js_e( 'يرجى اختيار إجراء جماعي', 'invitation-manager-pro' ); ?>');
            return;
        }
        
        // تأكيد الحذف
        if (action === 'delete') {
            if (!confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف المدعوين المحددين؟', 'invitation-manager-pro' ) ); ?>')) {
                return;
            }
        }
        
        // تنفيذ الإجراء
        $('#bulk-action-form').append('<input type="hidden" name="guest_ids" value="' + selectedGuests.join(',') + '">');
        $('#bulk-action-form').submit();
    });
    
    // تحسين تجربة القائمة المنسدلة
    $('.impro-dropdown').hover(
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeIn(200);
        },
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeOut(200);
        }
    );
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // تحسين البحث
    $('.impro-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).closest('form').submit();
        }
    });
});
</script>