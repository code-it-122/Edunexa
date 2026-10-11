import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import LoginPage from './pages/auth/LoginPage';
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage';
import OtpPage from './pages/auth/OtpPage';
import ResetPasswordPage from './pages/auth/ResetPasswordPage';
import SelectCampusPage from './pages/common/SelectCampusPage';
import ChooseWorkspacePage from './pages/common/ChooseWorkspacePage';
import AppShell from './components/layout/AppShell';
import CommonDashboard from './pages/dashboard/CommonDashboard';

function ProtectedRoute({ children }) {
  const { token, loading } = useAuth();

  if (loading) {
    return (
      <div style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'var(--color-surface-bg)',
        fontFamily: 'Inter, sans-serif'
      }}>
        <div style={{ textAlign: 'center' }}>
          <div className="brand-icon-box" style={{ width: '48px', height: '48px', backgroundColor: 'var(--color-teal-primary)', color: '#FFFFFF', fontSize: '20px', margin: '0 auto 16px auto', borderRadius: '10px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>EN</div>
          <div style={{ fontSize: '15px', fontWeight: '700', color: 'var(--color-text-main)' }}>Loading EduNexa School OS...</div>
        </div>
      </div>
    );
  }

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  return children;
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* Public Auth Routes */}
          <Route path="/login" element={<LoginPage />} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/verify-otp" element={<OtpPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />

          {/* Protected Selection Routes */}
          <Route path="/select-campus" element={
            <ProtectedRoute>
              <SelectCampusPage />
            </ProtectedRoute>
          } />
          
          <Route path="/choose-workspace" element={
            <ProtectedRoute>
              <ChooseWorkspacePage />
            </ProtectedRoute>
          } />

          {/* Authenticated Application Shell Route */}
          <Route path="/app" element={
            <ProtectedRoute>
              <AppShell>
                <CommonDashboard />
              </AppShell>
            </ProtectedRoute>
          } />

          {/* Default Fallback */}
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
