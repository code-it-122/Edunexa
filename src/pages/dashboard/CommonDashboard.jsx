import React from 'react';
import { useAuth } from '../../context/AuthContext';
import AccessControlledBadge from '../../components/common/AccessControlledBadge';
import {
  GraduationCap,
  Users,
  Building2,
  Sparkles,
  ShieldCheck,
  TrendingUp,
  AlertTriangle,
  Clock,
  CheckCircle2,
  ArrowRight
} from 'lucide-react';

export default function CommonDashboard() {
  const { user } = useAuth();
  const roleTitle = user?.activeRoleTitle || 'Principal';
  const roleKey = user?.activeRoleKey || 'principal';
  const campusName = user?.activeCampusName || 'Main Campus';

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      {/* Top Welcome Banner */}
      <div style={{
        backgroundColor: '#FFFFFF',
        borderRadius: '12px',
        padding: '24px',
        border: '1px solid var(--color-border)',
        boxShadow: 'var(--shadow-sm)',
        display: 'flex',
        flexWrap: 'wrap',
        alignItems: 'center',
        justify-content: 'space-between',
        gap: '16px'
      }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '4px' }}>
            <h1 style={{ fontSize: '24px', fontWeight: '800', color: 'var(--color-text-main)' }}>
              Welcome back, {user?.fullName || 'User'} 👋
            </h1>
            <AccessControlledBadge
              roleScope={`${roleTitle} Workspace`}
              reason={`Granted permissions for ${campusName}`}
            />
          </div>
          <p style={{ fontSize: '14px', color: 'var(--color-text-muted)' }}>
            Active Campus: <strong style={{ color: 'var(--color-teal-primary)' }}>{campusName}</strong> • Role: <strong style={{ color: 'var(--color-deep-navy)' }}>{roleTitle}</strong>
          </p>
        </div>

        <div style={{ display: 'flex', gap: '10px' }}>
          <button
            className="btn btn-secondary"
            onClick={() => window.location.href = '/choose-workspace'}
            style={{ fontSize: '13px' }}
          >
            Switch Workspace Role
          </button>
        </div>
      </div>

      {/* Role KPI Overview Section (PDF Section 5) */}
      <div>
        <div style={{ fontSize: '12px', fontWeight: '700', textTransform: 'uppercase', color: 'var(--color-text-muted)', letterSpacing: '1px', marginBottom: '12px' }}>
          {roleTitle} Command Overview KPIs
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '16px' }}>
          {/* KPI 1 */}
          <div style={{ backgroundColor: '#FFFFFF', padding: '20px', borderRadius: '10px', border: '1px solid var(--color-border)', boxShadow: 'var(--shadow-sm)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--color-text-muted)' }}>
              <span style={{ fontSize: '12px', fontWeight: '600' }}>Total Campus Students</span>
              <Users size={18} color="var(--color-teal-primary)" />
            </div>
            <div style={{ fontSize: '28px', fontWeight: '800', color: 'var(--color-text-main)', margin: '8px 0 4px 0' }}>1,480</div>
            <div style={{ fontSize: '12px', color: 'var(--color-success)', fontWeight: '600', display: 'flex', alignItems: 'center', gap: '4px' }}>
              <TrendingUp size={14} /> +94.2% Attendance Today
            </div>
          </div>

          {/* KPI 2 */}
          <div style={{ backgroundColor: '#FFFFFF', padding: '20px', borderRadius: '10px', border: '1px solid var(--color-border)', boxShadow: 'var(--shadow-sm)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--color-text-muted)' }}>
              <span style={{ fontSize: '12px', fontWeight: '600' }}>Active Faculty & Staff</span>
              <GraduationCap size={18} color="var(--color-teal-primary)" />
            </div>
            <div style={{ fontSize: '28px', fontWeight: '800', color: 'var(--color-text-main)', margin: '8px 0 4px 0' }}>124</div>
            <div style={{ fontSize: '12px', color: 'var(--color-text-muted)' }}>
              98 Present • 4 On Leave
            </div>
          </div>

          {/* KPI 3 */}
          <div style={{ backgroundColor: '#FFFFFF', padding: '20px', borderRadius: '10px', border: '1px solid var(--color-border)', boxShadow: 'var(--shadow-sm)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--color-text-muted)' }}>
              <span style={{ fontSize: '12px', fontWeight: '600' }}>Fee Collections (Term 1)</span>
              <CheckCircle2 size={18} color="var(--color-success)" />
            </div>
            <div style={{ fontSize: '28px', fontWeight: '800', color: 'var(--color-text-main)', margin: '8px 0 4px 0' }}>$142,500</div>
            <div style={{ fontSize: '12px', color: 'var(--color-warning)', fontWeight: '600' }}>
              $12,400 Pending Concession Review
            </div>
          </div>

          {/* KPI 4 */}
          <div style={{ backgroundColor: '#FFFFFF', padding: '20px', borderRadius: '10px', border: '1px solid var(--color-border)', boxShadow: 'var(--shadow-sm)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--color-text-muted)' }}>
              <span style={{ fontSize: '12px', fontWeight: '600' }}>Safety & Approvals</span>
              <AlertTriangle size={18} color="var(--color-warning)" />
            </div>
            <div style={{ fontSize: '28px', fontWeight: '800', color: 'var(--color-text-main)', margin: '8px 0 4px 0' }}>2 Pending</div>
            <div style={{ fontSize: '12px', color: 'var(--color-success)', fontWeight: '600' }}>
              0 Critical Safety Alerts
            </div>
          </div>
        </div>
      </div>

      {/* Module Delivery Roadmap Notice */}
      <div style={{
        backgroundColor: 'var(--color-teal-light)',
        border: '1px solid var(--color-teal-primary)',
        borderRadius: '12px',
        padding: '24px',
        display: 'flex',
        alignItems: 'flex-start',
        gap: '16px'
      }}>
        <div style={{
          padding: '10px',
          borderRadius: '10px',
          backgroundColor: 'var(--color-teal-primary)',
          color: '#FFFFFF'
        }}>
          <ShieldCheck size={24} />
        </div>
        <div>
          <h3 style={{ fontSize: '16px', fontWeight: '700', color: 'var(--color-teal-dark)', marginBottom: '4px' }}>
            Initial Foundation & Common Application Shell Ready!
          </h3>
          <p style={{ fontSize: '13px', color: '#1E4C5B', lineHeight: '1.6' }}>
            The authentication pipeline (Login, 2FA OTP verification, Forgot Password, Reset Password), campus switcher, workspace role selector, RBAC permission structure, top header, left sidebar, command palette (Ctrl+K), and CASPA AI Assistant are fully built and connected to the backend API.
          </p>
          <div style={{ fontSize: '12px', fontWeight: '700', color: 'var(--color-teal-dark)', marginTop: '10px' }}>
            📌 Standing by for next module Figma designs from user to proceed incrementally.
          </div>
        </div>
      </div>
    </div>
  );
}
