import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Building2, Search, ArrowRight, CheckCircle2, ShieldCheck } from 'lucide-react';

export default function SelectCampusPage() {
  const { campuses, user, selectCampus } = useAuth();
  const [selectedId, setSelectedId] = useState(1);
  const [searchQuery, setSearchQuery] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    if (user?.activeCampusId) {
      setSelectedId(user.activeCampusId);
    }
  }, [user]);

  const filteredCampuses = (campuses && campuses.length > 0 ? campuses : [
    { id: 1, name: 'Main Campus', code: 'Center 01', location: 'Green Park, City' },
    { id: 2, name: 'North Campus', code: 'Trust', location: 'Green Park, Trust Area' },
    { id: 3, name: 'Lakeside Campus', code: 'Knowledge Park', location: 'Knowledge Park II' },
    { id: 4, name: 'Riverside Campus', code: 'South Hub', location: 'South Hub Road' },
    { id: 5, name: 'East Learning Center', code: 'Innovation Hub', location: 'Innovation Hub, Pune' },
  ]).filter(c =>
    c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    c.location.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handleContinue = async () => {
    setSubmitting(true);
    await selectCampus(selectedId);
    setSubmitting(false);
    navigate('/choose-workspace');
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
          <ShieldCheck size={16} color="var(--color-teal-primary)" />
          <span>Secure workspace access</span>
        </div>
      </header>

      {/* Main Content */}
      <main className="selection-content">
        <div className="selection-breadcrumb">
          ‹ YOUR SCHOOL NETWORK
        </div>
        <h1 className="selection-title">Select your campus</h1>
        <p className="selection-subtitle">Choose the campus you want to work in today.</p>

        {/* Search Input */}
        <div style={{ position: 'relative', maxWidth: '480px', marginBottom: '28px' }}>
          <Search size={18} style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: 'var(--color-text-light)' }} />
          <input
            type="text"
            className="form-input"
            style={{ paddingLeft: '42px', height: '44px' }}
            placeholder="Search by campus or location"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>

        {/* Grid of Selectable Campuses */}
        <div className="cards-grid">
          {filteredCampuses.map((campus) => {
            const isSelected = selectedId === campus.id;
            return (
              <div
                key={campus.id}
                className={`selectable-card ${isSelected ? 'selected' : ''}`}
                onClick={() => setSelectedId(campus.id)}
              >
                <div className="card-icon-avatar">
                  <Building2 size={20} />
                </div>
                <div className="card-info">
                  <div className="card-title">{campus.name}</div>
                  <div className="card-desc">📍 {campus.location}</div>
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
          {submitting ? 'Updating...' : 'Continue'} <ArrowRight size={16} />
        </button>
      </footer>
    </div>
  );
}
