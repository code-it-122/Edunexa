import React, { useState } from 'react';
import TopBar from './TopBar';
import LeftSidebar from './LeftSidebar';
import BottomNav from './BottomNav';
import CommandPalette from '../common/CommandPalette';
import CaspaAssistantDrawer from '../common/CaspaAssistantDrawer';

export default function AppShell({ children }) {
  const [isCommandOpen, setIsCommandOpen] = useState(false);
  const [isCaspaOpen, setIsCaspaOpen] = useState(false);

  return (
    <div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', backgroundColor: 'var(--color-surface-bg)' }}>
      {/* Top Header Bar */}
      <TopBar
        onOpenCommand={() => setIsCommandOpen(true)}
        onToggleCaspa={() => setIsCaspaOpen(!isCaspaOpen)}
      />

      {/* Main Body Area (Sidebar + Children Content) */}
      <div style={{ display: 'flex', flex: 1 }}>
        <LeftSidebar />

        <main style={{ flex: 1, padding: '24px', maxWidth: '1400px', width: '100%' }}>
          {children}
        </main>
      </div>

      {/* Mobile Bottom Navigation */}
      <BottomNav onToggleCaspa={() => setIsCaspaOpen(!isCaspaOpen)} />

      {/* Modals & Drawers */}
      <CommandPalette isOpen={isCommandOpen} onClose={() => setIsCommandOpen(false)} />
      <CaspaAssistantDrawer isOpen={isCaspaOpen} onClose={() => setIsCaspaOpen(false)} />
    </div>
  );
}
