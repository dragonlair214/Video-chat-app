// ----------------------------
// Imports
// ----------------------------
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const { ExpressPeerServer } = require('peer');
const { v4: uuidV4 } = require('uuid');
const path = require('path');
const mysql = require('mysql2/promise');

// ----------------------------
// MySQL Database Connection (Cloud Run + Cloud SQL)
// ----------------------------
const db = mysql.createPool({
  socketPath: process.env.DB_HOST,   // e.g. /cloudsql/project:region:instance
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME
});

// ----------------------------
// Express & Socket.IO Setup
// ----------------------------
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: '*' }
});

// ----------------------------
// PeerJS Setup
// ----------------------------
const peerServer = ExpressPeerServer(server, {
  debug: true,
  path: '/'
});
app.use('/peerjs', peerServer);

// ----------------------------
// View Engine & Static Files
// ----------------------------
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.static('public'));

// ----------------------------
// Routes
// ----------------------------
app.get('/', (req, res) => {
  res.redirect(`/${uuidV4()}`);
});

// ---------------
// IMPORTANT FIX:
// Pass counselorId + counselorName into room.ejs
// ---------------
app.get('/:room', async (req, res) => {
  const counselorId = req.query.counselor_id || null;
  let counselorName = null;

  // Load counselor name from database
  if (counselorId) {
    try {
      const [rows] = await db.query(
        "SELECT name FROM counselors WHERE id = ? LIMIT 1",
        [counselorId]
      );
      if (rows.length > 0) {
        counselorName = rows[0].name;
      }
    } catch (err) {
      console.error("Name fetch error:", err);
    }
  }

  res.render('room', { 
    roomId: req.params.room,
    counselorId,
    counselorName
  });
});

// ----------------------------
// WebSocket Handler
// ----------------------------
io.on('connection', socket => {

  socket.on('join-room', async (roomId, userId, counselorId) => {

    let userName = "Stranger";

    try {
      // Query counselor name from MySQL
      const [rows] = await db.query(
        "SELECT name FROM counselors WHERE id = ? LIMIT 1",
        [counselorId]
      );

      if (rows.length > 0) {
        userName = rows[0].name;
      }
    } catch (err) {
      console.error("Database Error:", err);
    }

    // Join room
    socket.join(roomId);

    // Notify other peers
    socket.to(roomId).emit('user-connected', { userId, name: userName });

    // Chat messages (with name)
    socket.on('chat-message', message => {
      socket.to(roomId).emit('chat-message', {
        name: userName,
        text: message
      });
    });

    // User disconnected
    socket.on('disconnect', () => {
      socket.to(roomId).emit('user-disconnected', userId);
    });
  });
});

// ----------------------------
// Start Server (Cloud Run)
// ----------------------------
const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
