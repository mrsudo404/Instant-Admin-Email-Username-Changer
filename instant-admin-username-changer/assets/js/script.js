/**
 * JavaScript for Instant Admin Email & Username Changer
 *
 * @package Instant_Admin_Username_Changer
 */

jQuery(document).ready(function($) {
    'use strict';

    // Admin Email Change Form
    $('#iauc-admin-email-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $('#iauc-change-email-btn');
        var $spinner = $button.siblings('.spinner');
        var $message = $('#iauc-email-message');
        
        var newEmail = $('#new_admin_email').val().trim();
        
        // Validation
        if (!newEmail) {
            showMessage($message, 'Please enter a new email address.', 'error');
            return;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(newEmail)) {
            showMessage($message, 'Please enter a valid email address.', 'error');
            return;
        }
        
        // Confirmation
        if (!confirm(iauc_ajax.strings.confirm_email_change)) {
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        clearMessage($message);
        
        // AJAX request
        $.ajax({
            url: iauc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iauc_change_admin_email',
                new_email: newEmail,
                nonce: iauc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage($message, response.data, 'success');
                    // Update the current email field
                    $('#current_admin_email').val(newEmail);
                    // Clear the form
                    $('#new_admin_email').val('');
                } else {
                    showMessage($message, response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showMessage($message, 'An error occurred. Please try again.', 'error');
                console.error('AJAX Error:', error);
            },
            complete: function() {
                // Hide loading state
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Username Change Form
    $('#iauc-username-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $('#iauc-change-username-btn');
        var $spinner = $button.siblings('.spinner');
        var $message = $('#iauc-username-message');
        
        var userId = $('#user_id').val();
        var newUsername = $('#new_username').val().trim();
        
        // Validation
        if (!userId) {
            showMessage($message, 'Please select a user.', 'error');
            return;
        }
        
        if (!newUsername) {
            showMessage($message, 'Please enter a new username.', 'error');
            return;
        }
        
        // Username validation
        var usernameRegex = /^[a-zA-Z0-9_-]+$/;
        if (!usernameRegex.test(newUsername)) {
            showMessage($message, 'Username can only contain letters, numbers, underscores, and hyphens.', 'error');
            return;
        }
        
        // Confirmation
        if (!confirm(iauc_ajax.strings.confirm_username_change)) {
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        clearMessage($message);
        
        // AJAX request
        $.ajax({
            url: iauc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iauc_change_username',
                user_id: userId,
                new_username: newUsername,
                nonce: iauc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage($message, response.data, 'success');
                    // Update the dropdown option text
                    updateUserDropdownOption(userId, newUsername);
                    // Clear the form
                    $('#user_id').val('');
                    $('#new_username').val('');
                } else {
                    showMessage($message, response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showMessage($message, 'An error occurred. Please try again.', 'error');
                console.error('AJAX Error:', error);
            },
            complete: function() {
                // Hide loading state
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Real-time username validation
    $('#new_username').on('input', function() {
        var username = $(this).val();
        var $message = $('#iauc-username-message');
        
        // Clear previous validation messages
        clearMessage($message);
        
        if (username) {
            // Username format validation
            var usernameRegex = /^[a-zA-Z0-9_-]+$/;
            if (!usernameRegex.test(username)) {
                showMessage($message, 'Username can only contain letters, numbers, underscores, and hyphens.', 'warning');
                return;
            }
            
            // Length validation
            if (username.length < 3) {
                showMessage($message, 'Username must be at least 3 characters long.', 'warning');
                return;
            }
            
            if (username.length > 60) {
                showMessage($message, 'Username must be less than 60 characters.', 'warning');
                return;
            }
        }
    });

    // Real-time email validation
    $('#new_admin_email').on('input', function() {
        var email = $(this).val();
        var $message = $('#iauc-email-message');
        
        // Clear previous validation messages
        clearMessage($message);
        
        if (email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage($message, 'Please enter a valid email address.', 'warning');
            }
        }
    });

    /**
     * Show message with specified type
     */
    function showMessage($container, message, type) {
        var cssClass = 'notice notice-' + type;
        $container.html('<div class="' + cssClass + '"><p>' + message + '</p></div>');
        $container.show();
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $container.fadeOut();
            }, 5000);
        }
    }

    /**
     * Clear message container
     */
    function clearMessage($container) {
        $container.empty().hide();
    }

    /**
     * Update user dropdown option after username change
     */
    function updateUserDropdownOption(userId, newUsername) {
        var $option = $('#user_id option[value="' + userId + '"]');
        var currentText = $option.text();
        
        // Update the username part in the option text
        var updatedText = currentText.replace(/\([^)]+\)/, '(' + newUsername + ')');
        $option.text(updatedText);
    }

    // Add some visual feedback for form interactions
    $('.iauc-form input, .iauc-form select').on('focus', function() {
        $(this).closest('tr').addClass('highlighted');
    }).on('blur', function() {
        $(this).closest('tr').removeClass('highlighted');
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + Enter to submit forms
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
            var $focused = $(':focus');
            if ($focused.closest('#iauc-admin-email-form').length) {
                $('#iauc-admin-email-form').submit();
            } else if ($focused.closest('#iauc-username-form').length) {
                $('#iauc-username-form').submit();
            }
        }
    });
}); 