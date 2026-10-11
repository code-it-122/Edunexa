import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { CheckCircle } from 'lucide-react';

export default function ResetPasswordPage() {
  const location = useLocation();
  const emailFromNav = location.state?.email || 'admin@edunexa.edu';

  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const { resetPassword } = useAuth();
  const navigate = useNavigate();

  const handleReset = async (e) => {
    e.preventDefault();
    if (password !== confirmPassword) {
      setErrorMsg('Passwords do not match.');
      return;
    }

    setErrorMsg('');
    setLoading(true);

    try {
      await resetPassword(emailFromNav, password, confirmPassword);
      setSuccess(true);
      setTimeout(() => {
        navigate('/login');
      }, 1500);
    } catch (err) {
      setErrorMsg(err.message || 'Password reset failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-split-container">
      {/* Left Banner */}
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
          <h2 className="auth-card-title">Reset Password</h2>
          <p className="auth-card-subtitle">Set your new password for account {emailFromNav}</p>

          {errorMsg && (
            <div className="alert alert-danger">
              <span>{errorMsg}</span>
            </div>
          )}

          {success ? (
            <div className="alert alert-success">
              <CheckCircle size={18} />
              <span>Password reset successfully! Redirecting to login...</span>
            </div>
          ) : (
            <form onSubmit={handleReset}>
              <div className="input-group">
                <label className="input-label">Enter Password</label>
                <input
                  type="password"
                  className="form-input"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>

              <div className="input-group">
                <label className="input-label">Confirm Password</label>
                <input
                  type="password"
                  className="form-input"
                  placeholder="••••••••"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  required
                />
              </div>

              <button
                type="submit"
                className="btn btn-primary btn-full"
                disabled={loading}
                style={{ marginTop: '12px', height: '46px', fontSize: '15px' }}
              >
                {loading ? 'Resetting...' : 'Log In'}
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
