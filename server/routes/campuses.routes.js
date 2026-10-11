import express from 'express';
import db from '../db/database.js';

const router = express.Router();

router.get('/', (req, res) => {
  const campuses = db.prepare('SELECT * FROM campuses WHERE status = "active"').all();
  res.json(campuses);
});

export default router;
