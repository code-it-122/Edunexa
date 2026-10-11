import React, { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem('edunexa_token') || null);
  const [user, setUser] = useState(null);
  const [workspaces, setWorkspaces] = useState([]);
  const [campuses, setCampuses] = useState([]);
  const [activeCampus, setActiveCampus] = useState(null);
  const [activeRole, setActiveRole] = useState(null);
  
  const [tempToken, setTempToken] = useState(null);
  const [otpEmail, setOtpEmail] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (token) {
      fetchCurrentUser(token);
    } else {
      setLoading(false);
    }
  }, [token]);

  const fetchCurrentUser = async (currentToken) => {
    try {
      setLoading(true);
      const res = await fetch('/api/auth/me', {
        headers: { Authorization: `Bearer ${currentToken}` }
      });
      const data = await res.json();
      if (res.ok) {
        setUser(data.user);
        setWorkspaces(data.workspaces || []);
        setCampuses(data.campuses || []);
        setActiveCampus(data.user.activeCampusId);
        setActiveRole(data.user.activeRoleKey);
        setError(null);
      } else {
        logout();
      }
    } catch (err) {
      console.error('Fetch me error:', err);
      setError('Connection error. Could not connect to backend server.');
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    setError(null);
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || 'Login failed.');
      }

      if (data.requireOtp) {
        setTempToken(data.tempToken);
        setOtpEmail(data.email);
        return { requireOtp: true, demoOtp: data.demoOtp };
      }
      return { requireOtp: false };
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };

  const verifyOtp = async (otpCode) => {
    setError(null);
    try {
      const res = await fetch('/api/auth/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: otpEmail, otp: otpCode, tempToken })
      });
      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || 'Verification failed.');
      }

      localStorage.setItem('edunexa_token', data.token);
      setToken(data.token);
      setUser(data.user);
      setWorkspaces(data.workspaces || []);
      setTempToken(null);
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };

  const forgotPassword = async (email) => {
    setError(null);
    try {
      const res = await fetch('/api/auth/forgot-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Request failed.');
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };

  const resetPassword = async (email, password, confirmPassword) => {
    setError(null);
    try {
      const res = await fetch('/api/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, confirmPassword })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Password reset failed.');
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };

  const selectCampus = async (campusId) => {
    try {
      const res = await fetch('/api/auth/select-campus', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({ campusId })
      });
      const data = await res.json();
      if (res.ok) {
        localStorage.setItem('edunexa_token', data.token);
        setToken(data.token);
        await fetchCurrentUser(data.token);
      }
    } catch (err) {
      console.error('Campus selection error:', err);
    }
  };

  const selectWorkspace = async (roleKey) => {
    try {
      const res = await fetch('/api/auth/select-workspace', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({ roleKey })
      });
      const data = await res.json();
      if (res.ok) {
        localStorage.setItem('edunexa_token', data.token);
        setToken(data.token);
        await fetchCurrentUser(data.token);
      }
    } catch (err) {
      console.error('Workspace selection error:', err);
    }
  };

  const logout = () => {
    localStorage.removeItem('edunexa_token');
    setToken(null);
    setUser(null);
    setWorkspaces([]);
    setTempToken(null);
    setOtpEmail('');
  };

  return (
    <AuthContext.Provider value={{
      token,
      user,
      workspaces,
      campuses,
      activeCampus,
      activeRole,
      tempToken,
      otpEmail,
      loading,
      error,
      setError,
      login,
      verifyOtp,
      forgotPassword,
      resetPassword,
      selectCampus,
      selectWorkspace,
      logout
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
