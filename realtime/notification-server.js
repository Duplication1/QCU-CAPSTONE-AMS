const http = require('http');
const { WebSocketServer } = require('ws');

const WS_PORT = parseInt(process.env.WS_PORT || '8081', 10);
const WS_NOTIFY_SECRET = process.env.WS_NOTIFY_SECRET || '';

const clientsByUser = new Map();

function normalizeUserId(value) {
  const parsed = parseInt(String(value || '').trim(), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function addClient(userId, socket) {
  if (!clientsByUser.has(userId)) {
    clientsByUser.set(userId, new Set());
  }
  clientsByUser.get(userId).add(socket);
}

function removeClient(userId, socket) {
  const sockets = clientsByUser.get(userId);
  if (!sockets) return;
  sockets.delete(socket);
  if (sockets.size === 0) {
    clientsByUser.delete(userId);
  }
}

function sendToUser(userId, payload) {
  const sockets = clientsByUser.get(userId);
  if (!sockets || sockets.size === 0) {
    return;
  }

  const message = JSON.stringify(payload);
  for (const socket of sockets) {
    if (socket.readyState === socket.OPEN) {
      socket.send(message);
    }
  }
}

function validateNotifySecret(req) {
  if (!WS_NOTIFY_SECRET) return true;
  const incoming = req.headers['x-notify-secret'];
  return incoming && incoming === WS_NOTIFY_SECRET;
}

function parseRequestBody(req) {
  return new Promise((resolve, reject) => {
    let raw = '';
    req.on('data', chunk => {
      raw += chunk;
      if (raw.length > 1_000_000) {
        req.destroy();
        reject(new Error('Payload too large'));
      }
    });

    req.on('end', () => {
      if (!raw) {
        resolve({});
        return;
      }
      try {
        resolve(JSON.parse(raw));
      } catch (error) {
        reject(new Error('Invalid JSON body'));
      }
    });

    req.on('error', reject);
  });
}

const server = http.createServer(async (req, res) => {
  const parsed = new URL(req.url, `http://${req.headers.host || '127.0.0.1'}`);

  if (req.method === 'GET' && parsed.pathname === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, ws_port: WS_PORT, connected_users: clientsByUser.size }));
    return;
  }

  if (req.method === 'POST' && parsed.pathname === '/notify') {
    if (!validateNotifySecret(req)) {
      res.writeHead(403, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ success: false, message: 'Forbidden' }));
      return;
    }

    try {
      const body = await parseRequestBody(req);
      const userIds = Array.isArray(body.user_ids) ? body.user_ids : [];
      const normalized = [...new Set(userIds.map(normalizeUserId).filter(Boolean))];
      const event = body.event || 'notification';

      normalized.forEach(userId => {
        sendToUser(userId, {
          event,
          user_id: userId,
          at: Date.now()
        });
      });

      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ success: true, delivered_to: normalized.length }));
    } catch (error) {
      res.writeHead(400, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ success: false, message: error.message }));
    }
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ success: false, message: 'Not found' }));
});

const wsServer = new WebSocketServer({ server });

wsServer.on('connection', (socket, req) => {
  const parsed = new URL(req.url, `http://${req.headers.host || '127.0.0.1'}`);
  const userId = normalizeUserId(parsed.searchParams.get('user_id'));

  if (!userId) {
    socket.close(1008, 'user_id required');
    return;
  }

  socket.userId = userId;
  addClient(userId, socket);

  socket.send(JSON.stringify({ event: 'connected', user_id: userId, at: Date.now() }));

  socket.on('close', () => {
    removeClient(userId, socket);
  });

  socket.on('error', () => {
    removeClient(userId, socket);
  });

  socket.on('message', raw => {
    const message = String(raw || '').trim();
    if (message === 'ping') {
      socket.send(JSON.stringify({ event: 'pong', at: Date.now() }));
    }
  });
});

server.listen(WS_PORT, () => {
  console.log(`[notification-server] running on port ${WS_PORT}`);
});
