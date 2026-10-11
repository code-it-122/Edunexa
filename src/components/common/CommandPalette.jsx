import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Search, GraduationCap, Users, BookOpen, Shield, Bus, Home, Settings, ArrowRight } from 'lucide-react';

const MODULE_COMMANDS = [
  { label: 'Principal Command Center', icon: GraduationCap, path: '/app', category: 'Dashboards' },
  { label: 'Admin Workspace & Settings', icon: Settings, path: '/app', category: 'Administration' },
  { label: 'Student Directory & Profiles', icon: Users, path: '/app', category: 'People' },
  { label: 'Academic Classes & Timetable', icon: BookOpen, path: '/app', category: 'Academics' },
  { label: 'Safety & Confidential Incidents', icon: Shield, path: '/app', category: 'Safety' },
  { label: 'Transport Fleet & Live Routes', icon: Bus, path: '/app', category: 'Operations' },
  { label: 'Hostel Rooms & Attendance', icon: Home, path: '/app', category: 'Operations' },
];

export default function CommandPalette({ isOpen, onClose }) {
  const [search, setSearch] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    const handleKeyDown = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isOpen) onClose();
        else {
          // Open handled by parent or toggle
        }
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen]);

  if (!isOpen) return null;

  const filtered = MODULE_COMMANDS.filter((c) =>
    c.label.toLowerCase().includes(search.toLowerCase()) ||
    c.category.toLowerCase().includes(search.toLowerCase())
  );

  const handleSelect = (path) => {
    navigate(path);
    onClose();
  };

  return (
    <div style={{
      position: 'fixed',
      inset: 0,
      backgroundColor: 'rgba(15, 23, 42, 0.5)',
      backdropFilter: 'blur(4px)',
      zIndex: 2000,
      display: 'flex',
      alignItems: 'flex-start',
      justifyContent: 'center',
      paddingTop: '100px'
    }} onClick={onClose}>
      <div style={{
        width: '560px',
        maxWidth: '90vw',
        backgroundColor: '#FFFFFF',
        borderRadius: '12px',
        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.2)',
        overflow: 'hidden',
        border: '1px solid var(--color-border)'
      }} onClick={(e) => e.stopPropagation()}>
        {/* Input Bar */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '12px',
          padding: '16px',
          borderBottom: '1px solid var(--color-border)'
        }}>
          <Search size={20} color="var(--color-teal-primary)" />
          <input
            type="text"
            autoFocus
            style={{
              flex: 1,
              border: 'none',
              outline: 'none',
              fontSize: '16px',
              color: 'var(--color-text-main)'
            }}
            placeholder="Type a command or search modules... (Esc to close)"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <kbd style={{
            fontSize: '11px',
            backgroundColor: '#F1F5F9',
            padding: '4px 8px',
            borderRadius: '4px',
            color: 'var(--color-text-muted)'
          }}>ESC</kbd>
        </div>

        {/* Command List */}
        <div style={{ maxHeight: '320px', overflowY: 'auto', padding: '8px' }}>
          {filtered.length === 0 ? (
            <div style={{ padding: '24px', textAlign: 'center', color: 'var(--color-text-muted)', fontSize: '14px' }}>
              No matching commands or modules found.
            </div>
          ) : (
            filtered.map((cmd, i) => {
              const Icon = cmd.icon;
              return (
                <div
                  key={i}
                  onClick={() => handleSelect(cmd.path)}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justify-content: 'space-between',
                    padding: '12px 14px',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease'
                  }}
                  onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'var(--color-surface-bg)'}
                  onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <div style={{
                      padding: '8px',
                      borderRadius: '6px',
                      backgroundColor: 'var(--color-teal-light)',
                      color: 'var(--color-teal-primary)'
                    }}>
                      <Icon size={18} />
                    </div>
                    <div>
                      <div style={{ fontSize: '14px', fontWeight: '600', color: 'var(--color-text-main)' }}>{cmd.label}</div>
                      <div style={{ fontSize: '11px', color: 'var(--color-text-muted)' }}>{cmd.category}</div>
                    </div>
                  </div>
                  <ArrowRight size={16} color="var(--color-text-light)" />
                </div>
              );
            })
          )}
        </div>
      </div>
    </div>
  );
}
