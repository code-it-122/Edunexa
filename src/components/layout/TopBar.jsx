import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Search, Sparkles, Bell, Building2, ChevronDown, User, LogOut, ShieldCheck, RefreshCw } from 'lucide-react';

export default function TopBar({ onOpenCommand, onToggleCaspa, onToggleSidebar }) {
  const { user, campuses, logout } = useAuth();
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const navigate = useNavigate();

  const activeCampusName = user?.activeCampusName || 'Main Campus';
  const activeRoleTitle = user?.activeRoleTitle || 'Workspace';

  return (
    <header style={{
      height: 'var(--topbar-height)',
      backgroundColor: '#FFFFFF',
      borderBottom: '1px solid var(--color-border)',
      padding: '0 24px',
      display: 'flex',
      alignItems: 'center',
      justify-content: 'space-between',
      position: 'sticky',
      top: 0,
      zIndex: 500
    }}>
      {/* Left: Brand Logo & Campus Switcher */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
        <div className="auth-logo-brand" style={{ color: 'var(--color-deep-navy)', cursor: 'pointer' }} onClick={() => navigate('/app')}>
          <div className="brand-icon-box" style={{ backgroundColor: 'var(--color-teal-primary)', color: '#FFFFFF' }}>EN</div>
          <span>EduNexa</span>
        </div>

        {/* Campus Selector Badge */}
        <button
          onClick={() => navigate('/select-campus')}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            padding: '6px 12px',
            borderRadius: '6px',
            border: '1px solid var(--color-border)',
            backgroundColor: 'var(--color-surface-bg)',
            fontSize: '12px',
            fontWeight: '600',
            color: 'var(--color-text-main)',
            cursor: 'pointer'
          }}
          title="Click to switch school campus"
        >
          <Building2 size={14} color="var(--color-teal-primary)" />
          <span>{activeCampusName}</span>
          <ChevronDown size={12} color="var(--color-text-muted)" />
        </button>
      </div>

      {/* Middle: Command Search Trigger */}
      <div style={{ flex: 1, maxWidth: '400px', margin: '0 20px' }}>
        <button
          onClick={onOpenCommand}
          style={{
            width: '100%',
            display: 'flex',
            alignItems: 'center',
            justify-content: 'space-between',
            padding: '8px 14px',
            borderRadius: '20px',
            border: '1px solid var(--color-border-dark)',
            backgroundColor: 'var(--color-surface-bg)',
            fontSize: '13px',
            color: 'var(--color-text-muted)',
            cursor: 'pointer'
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Search size={15} color="var(--color-teal-primary)" />
            <span>Search modules, students, commands...</span>
          </div>
          <kbd style={{
            fontSize: '10px',
            backgroundColor: '#FFFFFF',
            border: '1px solid #CBD5E1',
            padding: '2px 6px',
            borderRadius: '4px',
            fontWeight: '700'
          }}>Ctrl K</kbd>
        </button>
      </div>

      {/* Right Actions: Workspace Switcher, CASPA, Notifications & Profile */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
        {/* Active Role / Workspace Switcher */}
        <button
          onClick={() => navigate('/choose-workspace')}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            padding: '6px 12px',
            borderRadius: '6px',
            backgroundColor: 'var(--color-teal-light)',
            border: '1px solid var(--color-teal-primary)',
            fontSize: '12px',
            fontWeight: '700',
            color: 'var(--color-teal-primary)',
            cursor: 'pointer'
          }}
          title="Click to switch role workspace"
        >
          <RefreshCw size={13} />
          <span>{activeRoleTitle}</span>
        </button>

        {/* CASPA AI Toggle Button */}
        <button
          onClick={onToggleCaspa}
          className="btn btn-caspa"
          style={{ height: '36px', padding: '0 14px', fontSize: '12px', borderRadius: '20px' }}
        >
          <Sparkles size={15} />
          <span>CASPA AI</span>
        </button>

        {/* Notifications Bell */}
        <button style={{
          position: 'relative',
          width: '36px',
          height: '36px',
          borderRadius: '50%',
          border: '1px solid var(--color-border)',
          backgroundColor: '#FFFFFF',
          display: 'flex',
          alignItems: 'center',
          justify-content: 'center',
          cursor: 'pointer'
        }}>
          <Bell size={17} color="var(--color-text-main)" />
          <span style={{
            position: 'absolute',
            top: '4px',
            right: '4px',
            width: '8px',
            height: '8px',
            borderRadius: '50%',
            backgroundColor: 'var(--color-danger)'
          }} />
        </button>

        {/* User Profile Avatar Dropdown */}
        <div style={{ position: 'relative' }}>
          <button
            onClick={() => setShowProfileMenu(!showProfileMenu)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              border: 'none',
              background: 'none',
              cursor: 'pointer',
              padding: '4px'
            }}
          >
            <div style={{
              width: '34px',
              height: '34px',
              borderRadius: '50%',
              backgroundColor: 'var(--color-deep-navy)',
              color: '#FFFFFF',
              fontWeight: '700',
              fontSize: '13px',
              display: 'flex',
              alignItems: 'center',
              justify-content: 'center'
            }}>
              {user?.fullName ? user.fullName.substring(0, 2).toUpperCase() : 'US'}
            </div>
          </button>

          {showProfileMenu && (
            <div style={{
              position: 'absolute',
              right: 0,
              top: '44px',
              width: '220px',
              backgroundColor: '#FFFFFF',
              borderRadius: '8px',
              boxShadow: 'var(--shadow-md)',
              border: '1px solid var(--color-border)',
              padding: '8px',
              zIndex: 600
            }}>
              <div style={{ padding: '8px', borderBottom: '1px solid var(--color-border)' }}>
                <div style={{ fontSize: '13px', fontWeight: '700', color: 'var(--color-text-main)' }}>{user?.fullName}</div>
                <div style={{ fontSize: '11px', color: 'var(--color-text-muted)' }}>{user?.email}</div>
              </div>
              <button
                onClick={() => navigate('/choose-workspace')}
                style={{
                  width: '100%',
                  textAlign: 'left',
                  padding: '8px',
                  fontSize: '12px',
                  border: 'none',
                  background: 'none',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px'
                }}
              >
                <RefreshCw size={14} /> Switch Workspace Role
              </button>
              <button
                onClick={logout}
                style={{
                  width: '100%',
                  textAlign: 'left',
                  padding: '8px',
                  fontSize: '12px',
                  color: 'var(--color-danger)',
                  border: 'none',
                  background: 'none',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px'
                }}
              >
                <LogOut size={14} /> Log Out
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
