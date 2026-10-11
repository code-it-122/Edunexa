import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Sparkles, X, Send, Bot, User, CheckCircle2, Zap } from 'lucide-react';

export default function CaspaAssistantDrawer({ isOpen, onClose }) {
  const { user, token } = useAuth();
  const [query, setQuery] = useState('');
  const [messages, setMessages] = useState([]);
  const [suggestedPrompts, setSuggestedPrompts] = useState([]);
  const [loading, setLoading] = useState(false);

  const activeRole = user?.activeRoleKey || 'student';

  useEffect(() => {
    if (isOpen) {
      fetchPrompts();
      if (messages.length === 0) {
        setMessages([
          {
            sender: 'caspa',
            text: `Hello ${user?.fullName || 'User'}! I am **CASPA AI**, your school-intelligence assistant tuned for the **${user?.activeRoleTitle || 'Student'}** workspace. How can I help you today?`,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
          }
        ]);
      }
    }
  }, [isOpen, activeRole]);

  const fetchPrompts = async () => {
    try {
      const res = await fetch('/api/caspa/prompts', {
        headers: { Authorization: `Bearer ${token}` }
      });
      const data = await res.json();
      setSuggestedPrompts(data.prompts || []);
    } catch (err) {
      console.error('Failed to fetch CASPA prompts:', err);
    }
  };

  const handleSend = async (textToSend) => {
    const promptText = textToSend || query;
    if (!promptText.trim()) return;

    const userMsg = {
      sender: 'user',
      text: promptText,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    };

    setMessages((prev) => [...prev, userMsg]);
    if (!textToSend) setQuery('');
    setLoading(true);

    try {
      const res = await fetch('/api/caspa/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({ query: promptText })
      });
      const data = await res.json();

      const aiMsg = {
        sender: 'caspa',
        text: data.response,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        isAiGenerated: true
      };

      setMessages((prev) => [...prev, aiMsg]);
    } catch (err) {
      setMessages((prev) => [
        ...prev,
        { sender: 'caspa', text: 'Error connecting to CASPA AI engine.', timestamp: 'Just now' }
      ]);
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      right: 0,
      bottom: 0,
      width: '380px',
      maxWidth: '100vw',
      backgroundColor: '#FFFFFF',
      boxShadow: '-6px 0 25px rgba(0,0,0,0.15)',
      zIndex: 1000,
      display: 'flex',
      flexDirection: 'column',
      borderLeft: '1px solid var(--color-caspa-purple-light)'
    }}>
      {/* Header */}
      <div style={{
        backgroundColor: 'var(--color-caspa-purple)',
        color: '#FFFFFF',
        padding: '16px 20px',
        display: 'flex',
        alignItems: 'center',
        justify-content: 'space-between'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{ padding: '6px', backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: '8px' }}>
            <Sparkles size={20} />
          </div>
          <div>
            <div style={{ fontWeight: '800', fontSize: '16px', letterSpacing: '-0.3px' }}>CASPA AI</div>
            <div style={{ fontSize: '11px', opacity: 0.85 }}>{user?.activeRoleTitle || 'School'} Intelligence Assistant</div>
          </div>
        </div>
        <button
          onClick={onClose}
          style={{ background: 'none', border: 'none', color: '#FFFFFF', cursor: 'pointer', padding: '4px' }}
        >
          <X size={20} />
        </button>
      </div>

      {/* Messages Body */}
      <div style={{ flex: 1, padding: '16px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '14px', backgroundColor: '#FAF9FE' }}>
        {messages.map((msg, index) => (
          <div
            key={index}
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: msg.sender === 'user' ? 'flex-end' : 'flex-start'
            }}
          >
            <div style={{
              maxWidth: '85%',
              padding: '12px 14px',
              borderRadius: msg.sender === 'user' ? '14px 14px 2px 14px' : '14px 14px 14px 2px',
              backgroundColor: msg.sender === 'user' ? 'var(--color-teal-primary)' : '#FFFFFF',
              color: msg.sender === 'user' ? '#FFFFFF' : 'var(--color-text-main)',
              boxShadow: '0 2px 5px rgba(0,0,0,0.04)',
              fontSize: '13px',
              lineHeight: '1.5',
              border: msg.sender === 'caspa' ? '1px solid #E9D5FF' : 'none'
            }}>
              {msg.text}
            </div>

            <div style={{ fontSize: '10px', color: 'var(--color-text-light)', marginTop: '4px', display: 'flex', alignItems: 'center', gap: '4px' }}>
              {msg.timestamp}
              {msg.isAiGenerated && (
                <span style={{ color: 'var(--color-caspa-purple)', fontWeight: '600' }}>• AI Verified</span>
              )}
            </div>
          </div>
        ))}

        {loading && (
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: 'var(--color-caspa-purple)', fontSize: '12px', fontWeight: '600' }}>
            <Zap size={14} className="animate-spin" /> Thinking & verifying permissions...
          </div>
        )}
      </div>

      {/* Suggested Prompts */}
      {suggestedPrompts.length > 0 && (
        <div style={{ padding: '10px 16px', backgroundColor: '#FFFFFF', borderTop: '1px solid var(--color-border)' }}>
          <div style={{ fontSize: '11px', fontWeight: '700', color: 'var(--color-caspa-purple)', textTransform: 'uppercase', marginBottom: '6px' }}>
            Suggested Prompts ({user?.activeRoleTitle}):
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
            {suggestedPrompts.slice(0, 2).map((prompt, i) => (
              <button
                key={i}
                onClick={() => handleSend(prompt)}
                style={{
                  textAlign: 'left',
                  padding: '6px 10px',
                  borderRadius: '6px',
                  border: '1px solid #E9D5FF',
                  backgroundColor: 'var(--color-caspa-purple-light)',
                  color: 'var(--color-caspa-purple)',
                  fontSize: '11px',
                  fontWeight: '500',
                  cursor: 'pointer'
                }}
              >
                ✦ {prompt}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Input Footer */}
      <div style={{ padding: '14px', backgroundColor: '#FFFFFF', borderTop: '1px solid var(--color-border)' }}>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            handleSend();
          }}
          style={{ display: 'flex', gap: '8px' }}
        >
          <input
            type="text"
            className="form-input"
            style={{ fontSize: '13px', padding: '10px' }}
            placeholder={`Ask CASPA (${user?.activeRoleTitle} mode)...`}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
          <button
            type="submit"
            className="btn btn-caspa"
            style={{ width: '42px', height: '42px', padding: 0 }}
            disabled={loading}
          >
            <Send size={16} />
          </button>
        </form>
      </div>
    </div>
  );
}
