
/**
 * Public JavaScript for Invitation Manager Pro
 */

(function() {
    'use strict';

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initializePublic();
    });

    /**
     * Initialize public functionality
     */
    function initializePublic() {
        initializeRSVPForm();
        initializeAnimations();
        initializeAccessibility();
        initializeResponsiveFeatures();
        initializeInvitationViewer();
        initializeShareButtons();
        initializePrintFunctionality();
    }

    /**
     * Initialize RSVP form functionality
     */
    function initializeRSVPForm() {
        const form = document.getElementById('rsvp-form');
        if (!form) return;

        const radioOptions = document.querySelectorAll('.impro-radio-option');
        const plusOneSection = document.getElementById('plus-one-section');
        const plusOneNameSection = document.getElementById('plus-one-name-section');
        const plusOneCheckbox = document.querySelector('input[name="plus_one_attending"]');
        const submitBtn = document.getElementById('submit-btn');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        // Handle radio button interactions
        radioOptions.forEach(option => {
            option.addEventListener('click', function() {
                handleRadioSelection(this, radioOptions, plusOneSection);
            });

            // Keyboard support
            option.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleRadioSelection(this, radioOptions, plusOneSection);
                }
            });
        });

        // Handle plus one checkbox
        if (plusOneCheckbox) {
            plusOneCheckbox.addEventListener('change', function() {
                togglePlusOneNameSection(this.checked, plusOneNameSection);
            });
        }

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission(form, submitBtn, successMessage, errorMessage);
        });

        // Initialize form state
        initializeFormState(radioOptions, plusOneSection, plusOneNameSection, plusOneCheckbox);

        // Form validation
        initializeFormValidation(form);
    }

    /**
     * Handle radio button selection
     */
    function handleRadioSelection(selectedOption, allOptions, plusOneSection) {
        // Update visual state
        allOptions.forEach(opt => opt.classList.remove('selected'));
        selectedOption.classList.add('selected');
        
        // Update radio input
        const radioInput = selectedOption.querySelector('input[type="radio"]');
        radioInput.checked = true;

        // Handle plus one section visibility
        if (plusOneSection) {
            const isAccepted = selectedOption.dataset.value === 'accepted';
            togglePlusOneSection(isAccepted, plusOneSection);
        }

        // Trigger change event for accessibility
        radioInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Toggle plus one section visibility
     */
    function togglePlusOneSection(show, plusOneSection) {
        if (!plusOneSection) return;

        if (show) {
            plusOneSection.style.display = 'block';
            plusOneSection.classList.add('impro-fade-in');
            
            // Focus on plus one checkbox
            const checkbox = plusOneSection.querySelector('input[type="checkbox"]');
            if (checkbox) {
                setTimeout(() => checkbox.focus(), 300);
            }
        } else {
            plusOneSection.style.display = 'none';
            plusOneSection.classList.remove('impro-fade-in');
            
            // Reset plus one fields
            const checkbox = plusOneSection.querySelector('input[type="checkbox"]');
            const nameSection = document.getElementById('plus-one-name-section');
            
            if (checkbox) {
                checkbox.checked = false;
                togglePlusOneNameSection(false, nameSection);
            }
        }
    }

    /**
     * Toggle plus one name section
     */
    function togglePlusOneNameSection(show, nameSection) {
        if (!nameSection) return;

        if (show) {
            nameSection.style.display = 'block';
            nameSection.classList.add('impro-fade-in');
            
            // Focus on name input
            const nameInput = nameSection.querySelector('input[type="text"]');
            if (nameInput) {
                setTimeout(() => nameInput.focus(), 100);
            }
        } else {
            nameSection.style.display = 'none';
            nameSection.classList.remove('impro-fade-in');
            
            // Clear name input
            const nameInput = nameSection.querySelector('input[type="text"]');
            if (nameInput) {
                nameInput.value = '';
            }
        }
    }

    /**
     * Initialize form state based on existing values
     */
    function initializeFormState(radioOptions, plusOneSection, plusOneNameSection, plusOneCheckbox) {
        // Check for pre-selected radio
        const checkedRadio = document.querySelector('input[name="status"]:checked');
        if (checkedRadio) {
            const option = checkedRadio.closest('.impro-radio-option');
            if (option) {
                option.classList.add('selected');
                
                if (checkedRadio.value === 'accepted' && plusOneSection) {
                    togglePlusOneSection(true, plusOneSection);
                    
                    if (plusOneCheckbox && plusOneCheckbox.checked && plusOneNameSection) {
                        togglePlusOneNameSection(true, plusOneNameSection);
                    }
                }
            }
        }
    }

    /**
     * Handle form submission
     */
    function handleFormSubmission(form, submitBtn, successMessage, errorMessage) {
        // Validate form
        if (!validateForm(form)) {
            return;
        }

        // Prepare form data
        const formData = new FormData(form);
        formData.append('action', 'impro_submit_rsvp');
        formData.append('nonce', impro_public.nonce);

        // Update button state
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="impro-spinner"></span> ' + (impro_public.strings.submitting || 'جاري الإرسال...');

        // Hide previous messages
        hideMessage(successMessage);
        hideMessage(errorMessage);

        // Submit form
        fetch(impro_public.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showMessage(successMessage, data.data.message || (impro_public.strings.submitted || 'تم إرسال ردكم بنجاح'));
                hideForm(form);
                
                // Scroll to success message
                successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Trigger custom event
                document.dispatchEvent(new CustomEvent('impro.rsvp.submitted', {
                    detail: { data: data.data }
                }));
            } else {
                showMessage(errorMessage, data.data || (impro_public.strings.error || 'حدث خطأ أثناء الإرسال'));
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })
        .catch(error => {
            console.error('RSVP submission error:', error);
            showMessage(errorMessage, impro_public.strings.error || 'حدث خطأ أثناء الإرسال');
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    /**
     * Initialize form validation
     */
    function initializeFormValidation(form) {
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });
            
            field.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }

    /**
     * Validate entire form
     */
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Clear previous errors
        clearAllErrors(form);
        
        // Validate each required field
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        // Custom validation for radio buttons
        const statusRadios = form.querySelectorAll('input[name="status"]');
        if (statusRadios.length > 0) {
            const isStatusSelected = Array.from(statusRadios).some(radio => radio.checked);
            if (!isStatusSelected) {
                showFieldError(statusRadios[0], impro_public.strings.required || 'يرجى اختيار حالة الحضور');
                isValid = false;
            }
        }
        
        return isValid;
    }

    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        const isRequired = field.hasAttribute('required');
        
        // Clear previous error
        clearFieldError(field);
        
        // Required field validation
        if (isRequired && !value) {
            showFieldError(field, impro_public.strings.required || 'هذا الحقل مطلوب');
            return false;
        }
        
        // Email validation
        if (fieldType === 'email' && value && !isValidEmail(value)) {
            showFieldError(field, 'يرجى إدخال بريد إلكتروني صحيح');
            return false;
        }
        
        // Phone validation
        if (field.classList.contains('phone-field') && value && !isValidPhone(value)) {
            showFieldError(field, 'يرجى إدخال رقم هاتف صحيح');
            return false;
        }
        
        // Name validation
        if (field.classList.contains('name-field') && value && value.length < 2) {
            showFieldError(field, 'يرجى إدخال اسم صحيح');
            return false;
        }
        
        return true;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        field.classList.add('error');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.cssText = 'color: #dc2626; font-size: 12px; margin-top: 5px; direction: rtl;';
        
        field.parentNode.appendChild(errorDiv);
        
        // Add aria-describedby for accessibility
        const errorId = 'error-' + Date.now();
        errorDiv.id = errorId;
        field.setAttribute('aria-describedby', errorId);
    }

    /**
     * Clear field error
     */
    function clearFieldError(field) {
        field.classList.remove('error');
        const errorMessage = field.parentNode.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
        field.removeAttribute('aria-describedby');
    }

    /**
     * Clear all form errors
     */
    function clearAllErrors(form) {
        const errorFields = form.querySelectorAll('.error');
        const errorMessages = form.querySelectorAll('.error-message');
        
        errorFields.forEach(field => field.classList.remove('error'));
        errorMessages.forEach(message => message.remove());
    }

    /**
     * Show message
     */
    function showMessage(messageElement, text) {
        if (!messageElement) return;
        
        if (text) {
            messageElement.textContent = text;
        }
        
        messageElement.style.display = 'block';
        messageElement.classList.add('impro-fade-in');
    }

    /**
     * Hide message
     */
    function hideMessage(messageElement) {
        if (!messageElement) return;
        
        messageElement.style.display = 'none';
        messageElement.classList.remove('impro-fade-in');
    }

    /**
     * Hide form after successful submission
     */
    function hideForm(form) {
        form.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        form.style.opacity = '0';
        form.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            form.style.display = 'none';
        }, 500);
    }

    /**
     * Initialize animations
     */
    function initializeAnimations() {
        // Intersection Observer for fade-in animations
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('impro-fade-in');
                        entry.target.style.animationDelay = '0.1s';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            // Observe elements that should animate in
            const animateElements = document.querySelectorAll('.impro-invitation-card, .impro-rsvp-section, .impro-event-details, .impro-guest-info');
            animateElements.forEach(el => observer.observe(el));
        }
        
        // Add CSS for smooth animations
        addAnimationStyles();
    }

    /**
     * Add animation styles
     */
    function addAnimationStyles() {
        if (document.getElementById('impro-animation-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'impro-animation-styles';
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .impro-fade-in {
                animation: fadeInUp 0.6s ease-out forwards;
            }
            
            .impro-invitation-card {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.6s ease, transform 0.6s ease;
            }
            
            .impro-invitation-card.impro-fade-in {
                opacity: 1;
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Initialize accessibility features
     */
    function initializeAccessibility() {
        // Add ARIA labels to radio options
        const radioOptions = document.querySelectorAll('.impro-radio-option');
        radioOptions.forEach((option, index) => {
            option.setAttribute('role', 'radio');
            option.setAttribute('tabindex', index === 0 ? '0' : '-1');
            option.setAttribute('aria-checked', 'false');
            
            // Add descriptive labels
            const radioInput = option.querySelector('input[type="radio"]');
            if (radioInput) {
                option.setAttribute('aria-labelledby', radioInput.id + '-label');
            }
        });

        // Handle keyboard navigation for radio group
        radioOptions.forEach((option, index) => {
            option.addEventListener('keydown', function(e) {
                let nextIndex;
                
                switch(e.key) {
                    case 'ArrowDown':
                    case 'ArrowRight':
                        e.preventDefault();
                        nextIndex = (index + 1) % radioOptions.length;
                        break;
                    case 'ArrowUp':
                    case 'ArrowLeft':
                        e.preventDefault();
                        nextIndex = (index - 1 + radioOptions.length) % radioOptions.length;
                        break;
                    case ' ':
                        e.preventDefault();
                        option.click();
                        return;
                    default:
                        return;
                }
                
                // Update tabindex and focus
                radioOptions.forEach((opt, i) => {
                    opt.setAttribute('tabindex', i === nextIndex ? '0' : '-1');
                });
                radioOptions[nextIndex].focus();
            });
        });

        // Update ARIA states when selection changes
        document.addEventListener('change', function(e) {
            if (e.target.name === 'status') {
                radioOptions.forEach(option => {
                    const radio = option.querySelector('input[type="radio"]');
                    option.setAttribute('aria-checked', radio.checked ? 'true' : 'false');
                });
            }
        });

        // Add skip link for keyboard users
        addSkipLink();
        
        // Add landmark roles
        addLandmarkRoles();
    }

    /**
     * Add skip link for accessibility
     */
    function addSkipLink() {
        const rsvpSection = document.querySelector('.impro-rsvp-section');
        if (!rsvpSection) return;

        // Check if skip link already exists
        if (document.querySelector('.skip-link')) return;

        const skipLink = document.createElement('a');
        skipLink.href = '#rsvp-form';
        skipLink.textContent = 'الانتقال إلى نموذج الرد';
        skipLink.className = 'skip-link';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 1000;
            transition: top 0.3s;
            direction: rtl;
        `;

        skipLink.addEventListener('focus', function() {
            this.style.top = '6px';
        });

        skipLink.addEventListener('blur', function() {
            this.style.top = '-40px';
        });

        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    /**
     * Add landmark roles for screen readers
     */
    function addLandmarkRoles() {
        const mainContent = document.querySelector('main, .main-content');
        if (mainContent) {
            mainContent.setAttribute('role', 'main');
        }
        
        const nav = document.querySelector('nav');
        if (nav) {
            nav.setAttribute('role', 'navigation');
        }
        
        const footer = document.querySelector('footer');
        if (footer) {
            footer.setAttribute('role', 'contentinfo');
        }
    }

    /**
     * Initialize responsive features
     */
    function initializeResponsiveFeatures() {
        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                // Recalculate layout if needed
                window.dispatchEvent(new Event('resize'));
            }, 100);
        });

        // Optimize for touch devices
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
            
            // Add touch-friendly styles
            const style = document.createElement('style');
            style.textContent = `
                .touch-device .impro-radio-option {
                    min-height: 44px;
                    padding: 15px 20px;
                }
                .touch-device .impro-submit-btn {
                    min-height: 44px;
                    padding: 15px 30px;
                }
                .touch-device .impro-invitation-card {
                    margin: 10px;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Handle viewport changes
        handleViewportChanges();
    }

    /**
     * Handle viewport changes for responsive design
     */
    function handleViewportChanges() {
        const viewportMeta = document.querySelector('meta[name="viewport"]');
        if (!viewportMeta) {
            const meta = document.createElement('meta');
            meta.name = 'viewport';
            meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            document.head.appendChild(meta);
        }
    }

    /**
     * Initialize invitation viewer enhancements
     */
    function initializeInvitationViewer() {
        // Add zoom functionality for invitation images
        const invitationImages = document.querySelectorAll('.impro-invitation-image');
        invitationImages.forEach(img => {
            img.addEventListener('click', function() {
                openImageModal(this.src, this.alt);
            });
            
            // Add keyboard accessibility
            img.setAttribute('tabindex', '0');
            img.setAttribute('role', 'button');
            img.setAttribute('aria-label', 'عرض الصورة بحجم أكبر');
            
            img.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openImageModal(this.src, this.alt);
                }
            });
        });
        
        // Add QR code interaction
        const qrCodes = document.querySelectorAll('.impro-qr-code');
        qrCodes.forEach(qr => {
            qr.addEventListener('click', function() {
                showNotification('info', 'امسح الرمز لفتح الدعوة');
            });
        });
    }

    /**
     * Open image modal
     */
    function openImageModal(src, alt) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'impro-image-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            cursor: pointer;
        `;
        
        // Create image container
        const imgContainer = document.createElement('div');
        imgContainer.style.cssText = `
            max-width: 90%;
            max-height: 90%;
            position: relative;
        `;
        
        // Create image
        const img = document.createElement('img');
        img.src = src;
        img.alt = alt || 'صورة الدعوة';
        img.style.cssText = `
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
        `;
        
        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = `
            position: absolute;
            top: -40px;
            left: 0;
            background: none;
            border: none;
            color: white;
            font-size: 36px;
            cursor: pointer;
            width: 40px;
            height: 40px;
        `;
        
        // Assemble modal
        imgContainer.appendChild(img);
        imgContainer.appendChild(closeBtn);
        modal.appendChild(imgContainer);
        
        // Add event listeners
        modal.addEventListener('click', function(e) {
            if (e.target === modal || e.target === closeBtn) {
                document.body.removeChild(modal);
            }
        });
        
        // Close with Escape key
        document.addEventListener('keydown', function closeOnEscape(e) {
            if (e.key === 'Escape' && document.body.contains(modal)) {
                document.body.removeChild(modal);
                document.removeEventListener('keydown', closeOnEscape);
            }
        });
        
        // Add to DOM
        document.body.appendChild(modal);
    }

    /**
     * Initialize share buttons
     */
    function initializeShareButtons() {
        const shareButtons = document.querySelectorAll('.impro-share-button');
        shareButtons.forEach(button => {
            button.addEventListener('click', function() {
                const platform = this.dataset.platform;
                const url = this.dataset.url || window.location.href;
                const text = this.dataset.text || document.title;
                
                shareContent(platform, url, text);
            });
        });
    }

    /**
     * Share content on different platforms
     */
    function shareContent(platform, url, text) {
        let shareUrl;
        
        switch (platform) {
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
                break;
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(text)}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
                break;
            default:
                // Copy to clipboard
                copyToClipboard(url);
                showNotification('success', 'تم نسخ الرابط إلى الحافظة');
                return;
        }
        
        // Open share window
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }

    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).catch(err => {
                console.error('Failed to copy: ', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }

    /**
     * Fallback copy to clipboard
     */
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.cssText = 'position: fixed; top: -9999px; left: -9999px;';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }
        
        document.body.removeChild(textArea);
    }

    /**
     * Initialize print functionality
     */
    function initializePrintFunctionality() {
        const printButtons = document.querySelectorAll('.impro-print-button');
        printButtons.forEach(button => {
            button.addEventListener('click', function() {
                window.print();
            });
        });
    }

    /**
     * Show notification
     */
    function showNotification(type, message) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.impro-notification');
        existingNotifications.forEach(notif => notif.remove());
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `impro-notification impro-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 999999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 250px;
            max-width: 90%;
            text-align: center;
            direction: rtl;
        `;
        
        // Set background color based on type
        switch (type) {
            case 'success':
                notification.style.background = '#10b981';
                break;
            case 'error':
                notification.style.background = '#ef4444';
                break;
            case 'warning':
                notification.style.background = '#f59e0b';
                break;
            case 'info':
                notification.style.background = '#3b82f6';
                break;
            default:
                notification.style.background = '#6b7280';
        }
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 3000);
    }

    /**
     * Utility functions
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        // Simple phone validation - can be enhanced
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,20}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    /**
     * Polyfills for older browsers
     */
    function addPolyfills() {
        // Closest polyfill
        if (!Element.prototype.closest) {
            Element.prototype.closest = function(s) {
                var el = this;
                do {
                    if (el.matches(s)) return el;
                    el = el.parentElement || el.parentNode;
                } while (el !== null && el.nodeType === 1);
                return null;
            };
        }

        // Matches polyfill
        if (!Element.prototype.matches) {
            Element.prototype.matches = Element.prototype.msMatchesSelector || 
                                      Element.prototype.webkitMatchesSelector;
        }

        // CustomEvent polyfill
        if (typeof window.CustomEvent !== 'function') {
            window.CustomEvent = function(event, params) {
                params = params || { bubbles: false, cancelable: false, detail: null };
                var evt = document.createEvent('CustomEvent');
                evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
                return evt;
            };
            window.CustomEvent.prototype = window.Event.prototype;
        }
    }

    // Initialize polyfills
    addPolyfills();

})();
