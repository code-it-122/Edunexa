import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Lock, Mail, ChevronRight, ShieldCheck } from 'lucide-react';

export default function LoginPage() {
  const [email, setEmail] = useState('admin@edunexa.edu');
  const [password, setPassword] = useState('password123');
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    setLoading(true);

    try {
      const res = await login(email, password);
      if (res.requireOtp) {
        navigate('/verify-otp', { state: { demoOtp: res.demoOtp } });
      } else {
        navigate('/select-campus');
      }
    } catch (err) {
      setErrorMsg(err.message || 'Login failed. Please check credentials.');
    } finally {
      setLoading(false);
    }
  };

  const handleQuickDemoLogin = (demoEmail) => {
    setEmail(demoEmail);
    setPassword('password123');
  };

  return (
    <div className="auth-split-container">
      {/* Left Branding Banner (Matching Figma workplace screen) */}
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

      {/* Right Login Panel */}
      <div className="auth-right-panel">
        <div className="auth-card">
          <h2 className="auth-card-title">Log in to EduNEXA</h2>
          <p className="auth-card-subtitle">Enter your details to access your workspace.</p>

          {errorMsg && (
            <div className="alert alert-danger">
              <span>{errorMsg}</span>
            </div>
          )}

          <form onSubmit={handleLogin}>
            <div className="input-group">
              <label className="input-label">Email Or ID</label>
              <input
                type="email"
                className="form-input"
                placeholder="name@domain.edu"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            <div className="input-group">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <label className="input-label">Password</label>
                <Link to="/forgot-password" style={{ fontSize: '12px', color: 'var(--color-teal-primary)', fontWeight: '600' }}>
                  Forgot password?
                </Link>
              </div>
              <input
                type="password"
                className="form-input"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>

            <button type="submit" className="btn btn-primary btn-full" disabled={loading} style={{ marginTop: '12px', height: '46px', fontSize: '15px' }}>
              {loading ? 'Authenticating...' : 'Log In'}
            </button>
          </form>

          {/* Demo Account Quick Switcher */}
          <div style={{ marginTop: '32px', paddingTop: '20px', borderTop: '1px solid var(--color-border)' }}>
            <div style={{ fontSize: '11px', fontWeight: '700', color: 'var(--color-text-muted)', textTransform: 'uppercase', letterSpacing: '0.8px', marginBottom: '10px' }}>
              ⚡ Demo Accounts (Click to test):
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '6px' }}>
              <button
                type="button"
                className="btn btn-secondary"
                style={{ padding: '6px 10px', fontSize: '12px', justifyContent: 'flex-start' }}
                onClick={() => handleQuickDemoLogin('principal@edunexa.edu')}
              >
                🎓 Principal
              </button>
              <button
                type="button"
                className="btn btn-secondary"
                style={{ padding: '6px 10px', fontSize: '12px', justifyContent: 'flex-start' }}
                onClick={() => handleQuickDemoLogin('admin@edunexa.edu')}
              >
                ⚙️ Admin
              </button>
              <button
                type="button"
                className="btn btn-secondary"
                style={{ padding: '6px 10px', fontSize: '12px', justifyContent: 'flex-start' }}
                onClick={() => handleQuickDemoLogin('teacher@edunexa.edu')}
              >
                👩‍🏫 Teacher
              </button>
              <button
                type="button"
                className="btn btn-secondary"
                style={{ padding: '6px 10px', fontSize: '12px', justifyContent: 'flex-start' }}
                onClick={() => handleQuickDemoLogin('student@edunexa.edu')}
              >
                👨‍🎓 Student
              </button>
              <button
                type="button"
                className="btn btn-secondary"
                style={{ padding: '6px 10px', fontSize: '12px', justifyContent: 'flex-start' }}
                onClick={() => handleQuickDemoLogin('parent@edunexa.edu')}
              >
                👨‍👩‍👧 Parent
              </button>
              <button
                type="button"
                className="btn btn-secondary"
                style={{ padding: '6px 10px', fontSize: '12px', justifyContent: 'flex-start' }}
                onClick={() => handleQuickDemoLogin('safety@edunexa.edu')}
              >
                🛡️ Safety Officer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
