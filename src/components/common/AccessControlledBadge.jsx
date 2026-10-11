import React, { useState } from 'react';
import { ShieldCheck, Info } from 'lucide-react';

export default function AccessControlledBadge({ roleScope = 'Assigned Records', reason = 'Role-based access rule applied' }) {
  const [showTooltip, setShowTooltip] = useState(false);

  return (
    <div style={{ position: 'relative', display: 'inline-flex', alignItems: 'center' }}>
      <button
        type="button"
        onMouseEnter={() => setShowTooltip(true)}
        onMouseLeave={() => setShowTooltip(false)}
        onClick={() => setShowTooltip(!showTooltip)}
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: '5px',
          padding: '4px 10px',
          borderRadius: '20px',
          backgroundColor: '#F1F5F9',
          border: '1px solid #CBD5E1',
          fontSize: '11px',
          fontWeight: '600',
          color: '#334155',
          cursor: 'pointer',
          transition: 'all 0.2s ease'
        }}
      >
        <ShieldCheck size={13} color="var(--color-teal-primary)" />
        <span>Access Controlled</span>
        <Info size={11} color="var(--color-text-muted)" />
      </button>

      {showTooltip && (
        <div style={{
          position: 'absolute',
          bottom: '125%',
          left: '50%',
          transform: 'translateX(-50%)',
          width: '240px',
          padding: '12px',
          borderRadius: '8px',
          backgroundColor: '#0F172A',
          color: '#FFFFFF',
          fontSize: '12px',
          boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.3)',
          zIndex: 999,
          pointerEvents: 'none'
        }}>
          <div style={{ fontWeight: '700', color: '#38BDF8', marginBottom: '4px' }}>
            Why can I see this?
          </div>
          <div style={{ color: '#94A3B8', fontSize: '11px', lineHeight: '1.4' }}>
            {reason}. Scope: <strong>{roleScope}</strong>. Strict audit logging is active.
          </div>
        </div>
      )}
    </div>
  );
}
