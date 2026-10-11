import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import {
  LayoutDashboard,
  Users,
  GraduationCap,
  BookOpen,
  Calendar,
  ClipboardList,
  FileCheck,
  Receipt,
  Bus,
  Home,
  Shield,
  MessageSquare,
  BarChart3,
  Settings,
  Lock
} from 'lucide-react';

const SITEMAP = [
  {
    group: 'Main Workspace',
    items: [
      { label: 'Command Center', icon: LayoutDashboard, path: '/app', roles: ['all'] },
    ]
  },
  {
    group: 'People & Community',
    items: [
      { label: 'Student Directory', icon: Users, path: '/app', roles: ['principal', 'admin', 'teacher'] },
      { label: 'Teacher & Staff Directory', icon: GraduationCap, path: '/app', roles: ['principal', 'admin', 'hr'] },
      { label: 'Parent Portal & Contacts', icon: Users, path: '/app', roles: ['admin', 'parent'] },
    ]
  },
  {
    group: 'Academics & Exams',
    items: [
      { label: 'Classes & Sections', icon: BookOpen, path: '/app', roles: ['principal', 'admin', 'teacher'] },
      { label: 'Timetable & Schedule', icon: Calendar, path: '/app', roles: ['all'] },
      { label: 'Homework & Assignments', icon: ClipboardList, path: '/app', roles: ['teacher', 'student', 'parent'] },
      { label: 'Exams & Marks Entry', icon: FileCheck, path: '/app', roles: ['principal', 'teacher', 'student'] },
    ]
  },
  {
    group: 'Operations & Business',
    items: [
      { label: 'Accounts & Fee Receipts', icon: Receipt, path: '/app', roles: ['principal', 'accounts', 'parent'] },
      { label: 'Transport Fleet & Routes', icon: Bus, path: '/app', roles: ['principal', 'transport', 'parent'] },
      { label: 'Hostel Rooms & Attendance', icon: Home, path: '/app', roles: ['principal', 'warden', 'parent'] },
    ]
  },
  {
    group: 'Safeguarding & Safety',
    items: [
      { label: 'Anti-Ragging & Incident Reporting', icon: Shield, path: '/app', roles: ['principal', 'safety', 'counsellor'] },
      { label: 'Emergency Alerts', icon: Lock, path: '/app', roles: ['principal', 'admin', 'safety'] },
    ]
  },
  {
    group: 'Intelligence & Platform',
    items: [
      { label: 'Communication Center', icon: MessageSquare, path: '/app', roles: ['all'] },
      { label: 'Analytics & Reports', icon: BarChart3, path: '/app', roles: ['principal', 'admin', 'accounts', 'hr'] },
      { label: 'RBAC Roles & Permissions', icon: Settings, path: '/app', roles: ['admin', 'principal'] },
    ]
  }
];

export default function LeftSidebar() {
  const { user } = useAuth();
  const activeRole = user?.activeRoleKey || 'student';

  return (
    <aside style={{
      width: 'var(--sidebar-width)',
      backgroundColor: '#FFFFFF',
      borderRight: '1px solid var(--color-border)',
      minHeight: 'calc(100vh - var(--topbar-height))',
      padding: '16px 12px',
      display: 'flex',
      flexDirection: 'column',
      gap: '20px',
      flexShrink: 0
    }}>
      {/* Active Role Indicator Card */}
      <div style={{
        padding: '12px',
        borderRadius: '8px',
        backgroundColor: 'var(--color-surface-bg)',
        border: '1px solid var(--color-border)'
      }}>
        <div style={{ fontSize: '10px', fontWeight: '800', textTransform: 'uppercase', color: 'var(--color-teal-primary)', letterSpacing: '0.8px' }}>
          CURRENT WORKSPACE
        </div>
        <div style={{ fontSize: '14px', fontWeight: '700', color: 'var(--color-text-main)', marginTop: '2px' }}>
          {user?.activeRoleTitle || 'Student'} Workspace
        </div>
      </div>

      {/* Dynamic Nav Items */}
      <nav style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
        {SITEMAP.map((section, idx) => {
          const visibleItems = section.items.filter(
            item => item.roles.includes('all') || item.roles.includes(activeRole)
          );

          if (visibleItems.length === 0) return null;

          return (
            <div key={idx}>
              <div style={{
                fontSize: '10px',
                fontWeight: '700',
                textTransform: 'uppercase',
                color: 'var(--color-text-light)',
                letterSpacing: '1px',
                padding: '0 8px 6px 8px'
              }}>
                {section.group}
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                {visibleItems.map((item, i) => {
                  const Icon = item.icon;
                  return (
                    <NavLink
                      key={i}
                      to={item.path}
                      style={({ isActive }) => ({
                        display: 'flex',
                        alignItems: 'center',
                        gap: '10px',
                        padding: '8px 12px',
                        borderRadius: '6px',
                        fontSize: '13px',
                        fontWeight: isActive ? '700' : '500',
                        color: isActive ? 'var(--color-teal-primary)' : 'var(--color-text-main)',
                        backgroundColor: isActive ? 'var(--color-teal-light)' : 'transparent',
                        textDecoration: 'none',
                        transition: 'all 0.15s ease'
                      })}
                    >
                      <Icon size={16} color={activeRole ? 'var(--color-teal-primary)' : undefined} />
                      <span>{item.label}</span>
                    </NavLink>
                  );
                })}
              </div>
            </div>
          );
        })}
      </nav>
    </aside>
  );
}
