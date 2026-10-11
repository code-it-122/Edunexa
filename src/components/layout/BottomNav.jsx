import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Home, BookOpen, Sparkles, User, Menu } from 'lucide-react';

export default function BottomNav({ onToggleCaspa }) {
  const { user } = useAuth();
  const activeRole = user?.activeRoleKey || 'student';

  return (
    <nav style={{
      position: 'fixed',
      bottom: 0,
      left: 0,
      right: 0,
      height: '60px',
      backgroundColor: '#FFFFFF',
      borderTop: '1px solid var(--color-border)',
      display: 'none', // Controlled via CSS media queries in main stylesheet
      alignItems: 'center',
      justify-content: 'space-around',
      zIndex: 800,
      boxShadow: '0 -2px 10px rgba(0,0,0,0.05)'
    }} className="mobile-bottom-nav">
      <NavLink
        to="/app"
        style={({ isActive }) => ({
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: '2px',
          fontSize: '10px',
          fontWeight: '600',
          color: isActive ? 'var(--color-teal-primary)' : 'var(--color-text-muted)',
          textDecoration: 'none'
        })}
      >
        <Home size={18} />
        <span>Home</span>
      </NavLink>

      <NavLink
        to="/app"
        style={({ isActive }) => ({
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: '2px',
          fontSize: '10px',
          fontWeight: '600',
          color: isActive ? 'var(--color-teal-primary)' : 'var(--color-text-muted)',
          textDecoration: 'none'
        })}
      >
        <BookOpen size={18} />
        <span>Workspaces</span>
      </NavLink>

      <button
        onClick={onToggleCaspa}
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: '2px',
          fontSize: '10px',
          fontWeight: '700',
          color: 'var(--color-caspa-purple)',
          background: 'none',
          border: 'none',
          cursor: 'pointer'
        }}
      >
        <div style={{
          width: '32px',
          height: '32px',
          borderRadius: '50%',
          backgroundColor: 'var(--color-caspa-purple)',
          color: '#FFFFFF',
          display: 'flex',
          alignItems: 'center',
          justify-content: 'center'
        }}>
          <Sparkles size={16} />
        </div>
        <span>CASPA</span>
      </button>

      <NavLink
        to="/choose-workspace"
        style={({ isActive }) => ({
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: '2px',
          fontSize: '10px',
          fontWeight: '600',
          color: isActive ? 'var(--color-teal-primary)' : 'var(--color-text-muted)',
          textDecoration: 'none'
        })}
      >
        <User size={18} />
        <span>Role</span>
      </NavLink>
    </nav>
  );
}
