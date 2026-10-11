// assets/js/auth.js - jQuery & AJAX Authentication Handler

$(document).ready(function () {
    // 1. Quick Demo Account Selector
    $('.btn-demo-account').on('click', function () {
        const email = $(this).data('email');
        $('#loginEmail').val(email);
        $('#loginPassword').val('password123');
    });

    // 2. Login Form AJAX Submission
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $alert = $('#authAlert');

        $alert.addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Authenticating...');

        const payload = {
            email: $('#loginEmail').val(),
            password: $('#loginPassword').val()
        };

        $.ajax({
            url: 'api/auth.php?action=login',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function (res) {
                if (res.requireOtp) {
                    window.location.href = 'index.php?page=otp';
                } else {
                    window.location.href = 'index.php?page=select-campus';
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Login failed. Please check credentials.';
                $alert.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                $btn.prop('disabled', false).text('Log In');
            }
        });
    });

    // 3. 6-Digit OTP Auto-focus & Submission
    $('.otp-field').on('input', function () {
        const val = $(this).val();
        if (val.length === 1) {
            $(this).next('.otp-field').focus();
        }
    });

    $('.otp-field').on('keydown', function (e) {
        if (e.key === 'Backspace' && !$(this).val()) {
            $(this).prev('.otp-field').focus();
        }
    });

    // Handle paste in OTP
    $('.otp-field').first().on('paste', function (e) {
        const pasteData = (e.originalEvent.clipboardData || window.clipboardData).getData('text').trim();
        if (/^\d{6}$/.test(pasteData)) {
            e.preventDefault();
            $('.otp-field').each(function (i) {
                $(this).val(pasteData[i]);
            });
            $('.otp-field').last().focus();
        }
    });

    $('#otpForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $alert = $('#otpAlert');

        let otpCode = '';
        $('.otp-field').each(function () {
            otpCode += $(this).val();
        });

        if (otpCode.length !== 6) {
            $alert.removeClass('d-none').text('Please enter all 6 digits of the code.');
            return;
        }

        $alert.addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Verifying...');

        $.ajax({
            url: 'api/auth.php?action=verify_otp',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ otp: otpCode }),
            dataType: 'json',
            success: function (res) {
                window.location.href = res.redirect || 'index.php?page=select-campus';
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Verification failed.';
                $alert.removeClass('d-none').addClass('alert-danger').text(msg);
                $btn.prop('disabled', false).text('Verify');
            }
        });
    });

    // OTP Countdown Timer
    if ($('#otpCountdown').length) {
        let seconds = 29;
        const timer = setInterval(function () {
            seconds--;
            if (seconds >= 0) {
                $('#otpCountdown').text('00:' + (seconds < 10 ? '0' + seconds : seconds));
            } else {
                clearInterval(timer);
                $('#resendBlock').html('<a href="#" id="resendLink" style="color: var(--caspa-teal); font-weight: 600;">Resend Code Now</a>');
            }
        }, 1000);

        $(document).on('click', '#resendLink', function (e) {
            e.preventDefault();
            alert('A new 6-digit demonstration code has been sent: 123456');
            location.reload();
        });
    }

    // 4. Forgot Password Submission
    $('#forgotPasswordForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const $alert = $('#forgotAlert');
        const email = $('#forgotEmail').val();

        $alert.addClass('d-none');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sending...');

        $.ajax({
            url: 'api/auth.php?action=forgot_password',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ email: email }),
            dataType: 'json',
            success: function (res) {
                $alert.removeClass('d-none alert-danger').addClass('alert-success').html(
                    '<strong>Code Sent!</strong> Demo OTP Code is <code>' + (res.demoOtp || '123456') + '</code>'
                );
                $('#actionProceed').removeClass('d-none');
                $btn.prop('disabled', false).text('Send Reset Link');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Request failed.';
                $alert.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                $btn.prop('disabled', false).text('Send Reset Link');
            }
        });
    });

    // 5. Reset Password Submission
    $('#resetPasswordForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const $alert = $('#resetAlert');

        const p1 = $('#newPassword').val();
        const p2 = $('#confirmPassword').val();

        if (p1 !== p2) {
            $alert.removeClass('d-none').addClass('alert-danger').text('Passwords do not match.');
            return;
        }

        $alert.addClass('d-none');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');

        $.ajax({
            url: 'api/auth.php?action=reset_password',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                password: p1,
                confirm_password: p2
            }),
            dataType: 'json',
            success: function (res) {
                $alert.removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
                setTimeout(function () {
                    window.location.href = 'index.php?page=login';
                }, 1500);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Password update failed.';
                $alert.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                $btn.prop('disabled', false).text('Reset Password');
            }
        });
    });

    // 6. Campus Selection
    $('.campus-card-select').on('click', function () {
        $('.campus-card-select').removeClass('active-selected');
        $(this).addClass('active-selected');
    });

    $('#btnContinueCampus').on('click', function () {
        const selectedId = $('.campus-card-select.active-selected').data('id') || 1;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Continuing...');

        $.ajax({
            url: 'api/campus.php?action=select',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ campus_id: selectedId }),
            dataType: 'json',
            success: function (res) {
                window.location.href = res.redirect || 'index.php?page=choose-workspace';
            },
            error: function () {
                $btn.prop('disabled', false).text('Continue →');
                alert('Failed to select campus.');
            }
        });
    });

    // Campus Search Filter
    $('#campusSearchInput').on('keyup', function () {
        const query = $(this).val().toLowerCase();
        $('.campus-card-select').each(function () {
            const title = $(this).find('.card-title-text').text().toLowerCase();
            const desc = $(this).find('.card-desc-text').text().toLowerCase();
            if (title.includes(query) || desc.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // 7. Workspace Selection
    $('.workspace-card-select').on('click', function () {
        $('.workspace-card-select').removeClass('active-selected');
        $(this).addClass('active-selected');
    });

    $('#btnContinueWorkspace').on('click', function () {
        const selectedRole = $('.workspace-card-select.active-selected').data('role') || 'principal';
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Entering...');

        $.ajax({
            url: 'api/workspace.php?action=select',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ role_key: selectedRole }),
            dataType: 'json',
            success: function (res) {
                window.location.href = res.redirect || 'index.php?page=dashboard';
            },
            error: function () {
                $btn.prop('disabled', false).text('Continue →');
                alert('Failed to select workspace.');
            }
        });
    });

    // 8. Logout Handler
    $('#btnLogout, .btn-action-logout').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'api/auth.php?action=logout',
            type: 'POST',
            dataType: 'json',
            success: function (res) {
                window.location.href = res.redirect || 'index.php?page=login';
            }
        });
    });
});
