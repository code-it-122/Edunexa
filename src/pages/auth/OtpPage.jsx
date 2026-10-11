import React, { useState, useRef, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

export default function OtpPage() {
  const [digits, setDigits] = useState(['1', '2', '3', '4', '5', '6']); // Pre-filled with demo code for effortless testing
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [timer, setTimer] = useState(29);
  const inputsRef = useRef([]);

  const { verifyOtp, otpEmail } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const demoCode = location.state?.demoOtp || '123456';

  useEffect(() => {
    const interval = setInterval(() => {
      setTimer((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const handleChange = (index, value) => {
    if (!/^\d*$/.test(value)) return;

    const newDigits = [...digits];
    newDigits[index] = value.slice(-1);
    setDigits(newDigits);

    // Auto-focus next input
    if (value && index < 5) {
      inputsRef.current[index + 1]?.focus();
    }
  };

  const handleKeyDown = (index, e) => {
    if (e.key === 'Backspace' && !digits[index] && index > 0) {
      inputsRef.current[index - 1]?.focus();
    }
  };

  const handleVerify = async (e) => {
    e.preventDefault();
    const otpCode = digits.join('');
    if (otpCode.length < 6) {
      setErrorMsg('Please enter all 6 digits.');
      return;
    }

    setErrorMsg('');
    setLoading(true);

    try {
      await verifyOtp(otpCode);
      navigate('/select-campus');
    } catch (err) {
      setErrorMsg(err.message || 'OTP verification failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-split-container">
      {/* Left Branding Banner */}
      <div className="auth-left-banner">
        <div className="auth-logo-brand">
          <div className="brand-icon-box">EN</div>
          <span>EduNexa</span>
        </div>

        <div className="auth-banner-content">
          <span className="auth-tagline">ONE SCHOOL. ONE PLATFORM.</span>
          <h1 className="auth-headline">Complete visibility. Controlled access. Intelligent automation.</h1>
          <p className="auth-subtext">
            A unified school operating system for Pre-Primary to Grade 12 — academics, operations, safety and AI in one role-based platform.
          </p>
        </div>

        <div className="auth-banner-footer">
          © EduNexa • CASPA AI Enabled
        </div>
      </div>

      {/* Right Form Panel */}
      <div className="auth-right-panel">
        <div className="auth-card">
          <h2 className="auth-card-title">Two-factor Authentication</h2>
          <p className="auth-card-subtitle">
            Enter the 6-digit code sent to your registered mobile/email ({otpEmail || 'user@edunexa.edu'}).
          </p>

          {errorMsg && (
            <div className="alert alert-danger">
              <span>{errorMsg}</span>
            </div>
          )}

          <div className="alert alert-info">
            <span>💡 <strong>Demo 2FA OTP Code:</strong> <code>{demoCode}</code> (Pre-filled below)</span>
          </div>

          <form onSubmit={handleVerify}>
            <div className="otp-input-container">
              {digits.map((digit, idx) => (
                <input
                  key={idx}
                  ref={(el) => (inputsRef.current[idx] = el)}
                  type="text"
                  maxLength={1}
                  className="otp-box"
                  value={digit}
                  onChange={(e) => handleChange(idx, e.target.value)}
                  onKeyDown={(e) => handleKeyDown(idx, e)}
                  required
                />
              ))}
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: '20px', fontSize: '12px', color: 'var(--color-text-muted)' }}>
              {timer > 0 ? (
                <span>Resend code in 00:{timer < 10 ? `0${timer}` : timer}</span>
              ) : (
                <button
                  type="button"
                  style={{ background: 'none', border: 'none', color: 'var(--color-teal-primary)', fontWeight: '600', cursor: 'pointer' }}
                  onClick={() => setTimer(30)}
                >
                  Resend Code Now
                </button>
              )}
            </div>

            <button
              type="submit"
              className="btn btn-primary btn-full"
              disabled={loading}
              style={{ height: '46px', fontSize: '15px' }}
            >
              {loading ? 'Verifying...' : 'Verify'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
