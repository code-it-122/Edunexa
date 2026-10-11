import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { ArrowLeft, Mail, CheckCircle } from 'lucide-react';

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [successInfo, setSuccessInfo] = useState(null);
  const [errorMsg, setErrorMsg] = useState('');
  const { forgotPassword } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    setLoading(true);

    try {
      const res = await forgotPassword(email);
      setSuccessInfo(res);
    } catch (err) {
      setErrorMsg(err.message || 'Error sending password reset email.');
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
          <h2 className="auth-card-title">Forgot Password</h2>
          <p className="auth-card-subtitle">We'll send a 6-digit OTP to your registered email</p>

          {errorMsg && (
            <div className="alert alert-danger">
              <span>{errorMsg}</span>
            </div>
          )}

          {successInfo ? (
            <div>
              <div className="alert alert-success">
                <CheckCircle size={18} />
                <div>
                  <strong>Reset Link Sent!</strong>
                  <div style={{ fontSize: '12px', marginTop: '2px' }}>
                    Demonstration OTP Code: <strong>{successInfo.demoOtp || '123456'}</strong>
                  </div>
                </div>
              </div>

              <button
                className="btn btn-primary btn-full"
                onClick={() => navigate('/reset-password', { state: { email } })}
                style={{ height: '46px', fontSize: '14px', marginTop: '12px' }}
              >
                Proceed to Reset Password →
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit}>
              <div className="input-group">
                <label className="input-label">Email</label>
                <input
                  type="email"
                  className="form-input"
                  placeholder="name@domain.edu"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>

              <button
                type="submit"
                className="btn btn-primary btn-full"
                disabled={loading}
                style={{ marginTop: '12px', height: '46px', fontSize: '15px' }}
              >
                {loading ? 'Sending...' : 'Send Reset Link'}
              </button>

              <div style={{ marginTop: '20px', textAlign: 'center' }}>
                <Link to="/login" style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '13px', fontWeight: '600' }}>
                  <ArrowLeft size={14} /> Back to Log In
                </Link>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
