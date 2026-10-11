import express from 'express';
import cors from 'cors';
import { initDatabase } from './db/database.js';
import authRoutes from './routes/auth.routes.js';
import campusRoutes from './routes/campuses.routes.js';
import caspaRoutes from './routes/caspa.routes.js';

const app = express();
const PORT = process.env.PORT || 5000;

// Middleware
app.use(cors());
app.use(express.json());

// Initialize Database
try {
  initDatabase();
  console.log('[DB] Database initialized successfully.');
} catch (err) {
  console.error('[DB Error]', err);
}

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/campuses', campusRoutes);
app.use('/api/caspa', caspaRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({ status: 'healthy', platform: 'EduNexa / CASPA OS', version: '1.0.0' });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('[Server Error]', err);
  res.status(500).json({ error: 'Internal server error occurred.' });
});

app.listen(PORT, () => {
  console.log(`[EduNexa Backend] Server running on http://localhost:${PORT}`);
});
