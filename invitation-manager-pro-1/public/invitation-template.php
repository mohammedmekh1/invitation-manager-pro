<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $event->name ); ?> - <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            direction: rtl;
        }
        
        .invitation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .invitation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .invitation-header {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .invitation-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .invitation-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .invitation-content {
            padding: 40px 30px;
        }
        
        .guest-name {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .event-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        .detail-icon {
            font-size: 1.5em;
            margin-left: 15px;
            width: 30px;
            text-align: center;
        }
        
        .invitation-text {
            font-size: 1.2em;
            line-height: 1.8;
            color: #555;
            text-align: center;
            margin: 30px 0;
        }
        
        .rsvp-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .rsvp-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 25px;
            border: 2px solid #e1e5e9;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .radio-option:hover {
            border-color: #667eea;
        }
        
        .radio-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .radio-option input[type="radio"] {
            display: none;
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .qr-code {
            text-align: center;
            margin: 30px 0;
        }
        
        .qr-code img {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }
        
        @media (max-width: 768px) {
            .invitation-container {
                padding: 10px;
            }
            
            .invitation-header {
                padding: 30px 20px;
            }
            
            .invitation-header h1 {
                font-size: 2em;
            }
            
            .invitation-content {
                padding: 30px 20px;
            }
            
            .radio-group {
                flex-direction: column;
                align-items: center;
            }
            
            .detail-item {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="invitation-container">
        <div class="invitation-card">
            <div class="invitation-header">
                <h1><?php echo esc_html( $event->name ); ?></h1>
            </div>
            
            <?php if ( $event->invitation_image_url ) : ?>
                <img src="<?php echo esc_url( $event->invitation_image_url ); ?>" alt="<?php echo esc_attr( $event->name ); ?>" class="invitation-image">
            <?php endif; ?>
            
            <div class="invitation-content">
                <div class="guest-name">
                    <?php printf( __( 'عزيزنا %s', 'invitation-manager-pro' ), esc_html( $guest->name ) ); ?>
                </div>
                
                <?php if ( $event->invitation_text ) : ?>
                    <div class="invitation-text">
                        <?php echo wp_kses_post( $event->invitation_text ); ?>
                    </div>
                <?php endif; ?>
                
                <div class="event-details">
                    <div class="detail-item">
                        <span class="detail-icon">📅</span>
                        <span><?php echo esc_html( date_i18n( 'l, j F Y', strtotime( $event->event_date ) ) ); ?></span>
                    </div>
                    
                    <?php if ( $event->event_time ) : ?>
                        <div class="detail-item">
                            <span class="detail-icon">🕐</span>
                            <span><?php echo esc_html( date_i18n( 'g:i A', strtotime( $event->event_time ) ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <span class="detail-icon">📍</span>
                        <span><?php echo esc_html( $event->venue ); ?></span>
                    </div>
                    
                    <?php if ( $event->address ) : ?>
                        <div class="detail-item">
                            <span class="detail-icon">🗺️</span>
                            <span><?php echo esc_html( $event->address ); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( $event->contact_info ) : ?>
                        <div class="detail-item">
                            <span class="detail-icon">📞</span>
                            <span><?php echo esc_html( $event->contact_info ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php
                // Generate QR code
                $public = new IMPRO_Public();
                echo $public->generate_invitation_qr( $token );
                ?>
            </div>
        </div>
        
        <div class="rsvp-section">
            <h2 style="text-align: center; margin-bottom: 30px; color: #333;">
                <?php _e( 'يرجى تأكيد حضوركم', 'invitation-manager-pro' ); ?>
            </h2>
            
            <div class="success-message" id="success-message">
                <?php _e( 'تم حفظ ردكم بنجاح! شكراً لكم.', 'invitation-manager-pro' ); ?>
            </div>
            
            <div class="error-message" id="error-message">
                <?php _e( 'حدث خطأ أثناء حفظ الرد. يرجى المحاولة مرة أخرى.', 'invitation-manager-pro' ); ?>
            </div>
            
            <form class="rsvp-form" id="rsvp-form">
                <input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
                
                <div class="form-group">
                    <label><?php _e( 'حالة الحضور', 'invitation-manager-pro' ); ?></label>
                    <div class="radio-group">
                        <label class="radio-option" data-value="accepted">
                            <input type="radio" name="status" value="accepted" <?php checked( $rsvp->status ?? '', 'accepted' ); ?>>
                            <span>✅ <?php _e( 'سأحضر', 'invitation-manager-pro' ); ?></span>
                        </label>
                        <label class="radio-option" data-value="declined">
                            <input type="radio" name="status" value="declined" <?php checked( $rsvp->status ?? '', 'declined' ); ?>>
                            <span>❌ <?php _e( 'معتذر', 'invitation-manager-pro' ); ?></span>
                        </label>
                    </div>
                </div>
                
                <?php if ( $public->can_bring_plus_one( $guest ) ) : ?>
                    <div class="form-group" id="plus-one-section" style="display: none;">
                        <label>
                            <input type="checkbox" name="plus_one_attending" value="1" <?php checked( $rsvp->plus_one_attending ?? 0, 1 ); ?>>
                            <?php _e( 'سأحضر مع مرافق', 'invitation-manager-pro' ); ?>
                        </label>
                    </div>
                    
                    <div class="form-group" id="plus-one-name-section" style="display: none;">
                        <label for="plus_one_name"><?php _e( 'اسم المرافق', 'invitation-manager-pro' ); ?></label>
                        <input type="text" id="plus_one_name" name="plus_one_name" value="<?php echo esc_attr( $rsvp->plus_one_name ?? '' ); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="dietary_requirements"><?php _e( 'متطلبات غذائية خاصة (اختياري)', 'invitation-manager-pro' ); ?></label>
                    <textarea id="dietary_requirements" name="dietary_requirements" rows="3" placeholder="<?php esc_attr_e( 'مثل: نباتي، خالي من الجلوتين، حساسية من المكسرات...', 'invitation-manager-pro' ); ?>"><?php echo esc_textarea( $rsvp->dietary_requirements ?? '' ); ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn">
                    <?php _e( 'تأكيد الرد', 'invitation-manager-pro' ); ?>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('rsvp-form');
            const radioOptions = document.querySelectorAll('.radio-option');
            const plusOneSection = document.getElementById('plus-one-section');
            const plusOneNameSection = document.getElementById('plus-one-name-section');
            const plusOneCheckbox = document.querySelector('input[name="plus_one_attending"]');
            const submitBtn = document.getElementById('submit-btn');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            // Handle radio button styling
            radioOptions.forEach(option => {
                option.addEventListener('click', function() {
                    radioOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                    
                    // Show/hide plus one section
                    if (plusOneSection) {
                        if (this.dataset.value === 'accepted') {
                            plusOneSection.style.display = 'block';
                        } else {
                            plusOneSection.style.display = 'none';
                            plusOneNameSection.style.display = 'none';
                            plusOneCheckbox.checked = false;
                        }
                    }
                });
            });
            
            // Handle plus one checkbox
            if (plusOneCheckbox) {
                plusOneCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        plusOneNameSection.style.display = 'block';
                    } else {
                        plusOneNameSection.style.display = 'none';
                    }
                });
            }
            
            // Initialize form state
            const checkedRadio = document.querySelector('input[name="status"]:checked');
            if (checkedRadio) {
                const option = checkedRadio.closest('.radio-option');
                option.classList.add('selected');
                
                if (checkedRadio.value === 'accepted' && plusOneSection) {
                    plusOneSection.style.display = 'block';
                    if (plusOneCheckbox && plusOneCheckbox.checked) {
                        plusOneNameSection.style.display = 'block';
                    }
                }
            }
            
            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                formData.append('action', 'impro_submit_rsvp');
                formData.append('nonce', impro_public.nonce);
                
                submitBtn.disabled = true;
                submitBtn.textContent = impro_public.strings.submitting;
                
                fetch(impro_public.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        successMessage.style.display = 'block';
                        errorMessage.style.display = 'none';
                        form.style.display = 'none';
                    } else {
                        errorMessage.style.display = 'block';
                        errorMessage.textContent = data.data || impro_public.strings.error;
                        successMessage.style.display = 'none';
                    }
                })
                .catch(error => {
                    errorMessage.style.display = 'block';
                    errorMessage.textContent = impro_public.strings.error;
                    successMessage.style.display = 'none';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php _e( 'تأكيد الرد', 'invitation-manager-pro' ); ?>';
                });
            });
        });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>

