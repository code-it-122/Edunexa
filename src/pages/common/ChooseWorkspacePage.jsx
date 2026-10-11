import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import {
  GraduationCap,
  ShieldAlert,
  UserCheck,
  BookOpen,
  Users,
  Briefcase,
  Receipt,
  Bus,
  Home,
  Shield,
  ArrowRight,
  CheckCircle2,
  Lock
} from 'lucide-react';

const ROLE_ICONS = {
  principal: GraduationCap,
  admin: ShieldAlert,
  teacher: BookOpen,
  student: UserCheck,
  parent: Users,
  hr: Briefcase,
  accounts: Receipt,
  transport: Bus,
  warden: Home,
  safety: Shield,
};

export default function ChooseWorkspacePage() {
  const { user, workspaces, selectWorkspace } = useAuth();
  const [selectedRole, setSelectedRole] = useState('principal');
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    if (user?.activeRoleKey) {
      setSelectedRole(user.activeRoleKey);
    }
  }, [user]);

  const availableWorkspaces = (workspaces && workspaces.length > 0) ? workspaces : [
    { key: 'principal', title: 'Principal', description: 'School command center, approvals, academics & finance' },
    { key: 'admin', title: 'Admin', description: 'Students, parents, classes, operations & RBAC' },
    { key: 'teacher', title: 'Teacher', description: 'Classes, attendance, lesson plans, homework & marks' },
    { key: 'student', title: 'Student', description: 'Learning, timetable, assignments, exams & CASPA' },
    { key: 'parent', title: 'Parent', description: 'Children progress, attendance, fees & notices' },
    { key: 'hr', title: 'HR', description: 'Employees, recruitment, attendance & payroll' },
    { key: 'accounts', title: 'Accounts', description: 'Fees, receipts, dues, expenses & budgets' },
    { key: 'safety', title: 'Safety Officer', description: 'Confidential incidents & safeguarding' },
  ];

  const handleContinue = async () => {
    setSubmitting(true);
    await selectWorkspace(selectedRole);
    setSubmitting(false);
    navigate('/app');
  };

  return (
    <div className="selection-page-container">
      {/* Header Bar */}
      <header className="selection-header">
        <div className="auth-logo-brand" style={{ color: 'var(--color-deep-navy)' }}>
          <div className="brand-icon-box" style={{ backgroundColor: 'var(--color-teal-primary)', color: '#FFFFFF' }}>EN</div>
          <span>EduNexa</span>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '12px', color: 'var(--color-text-muted)', fontWeight: '600' }}>
          <Lock size={15} color="var(--color-teal-primary)" />
          <span>Secure workspace access</span>
        </div>
      </header>

      {/* Main Content Area */}
      <main className="selection-content">
        <div className="selection-breadcrumb">
          ‹ {user?.activeCampusName?.toUpperCase() || 'MAIN CAMPUS - CENTER 01'}
        </div>
        <h1 className="selection-title">Choose your workspace</h1>
        <p className="selection-subtitle">You have access to more than one role at this school.</p>

        {/* Grid of Workspaces */}
        <div className="cards-grid">
          {availableWorkspaces.map((ws) => {
            const roleKey = ws.key || ws.role_id;
            const IconComp = ROLE_ICONS[roleKey] || GraduationCap;
            const isSelected = selectedRole === roleKey;

            return (
              <div
                key={roleKey}
                className={`selectable-card ${isSelected ? 'selected' : ''}`}
                onClick={() => setSelectedRole(roleKey)}
              >
                <div className="card-icon-avatar">
                  <IconComp size={22} />
                </div>
                <div className="card-info">
                  <div className="card-title">{ws.title}</div>
                  <div className="card-desc">{ws.description}</div>
                </div>
                {isSelected && (
                  <CheckCircle2 size={18} color="var(--color-teal-primary)" style={{ position: 'absolute', top: '16px', right: '16px' }} />
                )}
              </div>
            );
          })}
        </div>
      </main>

      {/* Sticky Bottom Bar */}
      <footer className="selection-footer-bar">
        <button
          className="btn btn-primary"
          style={{ height: '44px', padding: '0 28px', fontSize: '14px' }}
          onClick={handleContinue}
          disabled={submitting}
        >
          {submitting ? 'Entering Workspace...' : 'Continue'} <ArrowRight size={16} />
        </button>
      </footer>
    </div>
  );
}
