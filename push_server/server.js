const WebSocket = require('ws');
const express = require('express');
const redis = require('redis');
const bodyParser = require('body-parser');
const cors = require('cors');
const http = require('http');
const url = require('url');

const app = express();
const server = http.createServer(app);

const PUSH_PORT = process.env.PUSH_PORT || 8893;
const SUBSCRIBE_PORT = process.env.SUBSCRIBE_PORT || 8894;
const REDIS_HOST = process.env.REDIS_HOST || 'localhost';
const REDIS_PORT = process.env.REDIS_PORT || 6379;
const SIGNATURE_KEY = process.env.SIGNATURE_KEY || 'your-secret-key-123';

// Middleware
app.use(bodyParser.json());
app.use(cors());

// Redis клиент
const redisClient = redis.createClient({
    socket: {
        host: REDIS_HOST,
        port: REDIS_PORT
    }
});

// WebSocket сервер для подписок
const wss = new WebSocket.Server({
    server: server,
    path: '/bitrix/subws/'
});

// Хранилище подключений
const connections = new Map();

// Подключение к Redis
redisClient.connect().then(() => {
    console.log('✅ Connected to Redis');
}).catch(err => {
    console.error('❌ Redis connection error:', err);
});

// WebSocket соединения
wss.on('connection', (ws, request) => {
    try {
        const parsedUrl = url.parse(request.url, true);
        const channel = parsedUrl.query.channel;

        if (channel) {
            if (!connections.has(channel)) {
                connections.set(channel, new Set());
            }
            connections.get(channel).add(ws);

            console.log(`✅ Client subscribed to channel: ${channel}`);

            // Отправляем подтверждение подключения
            ws.send(JSON.stringify({
                type: 'connection',
                status: 'success',
                channel: channel
            }));
        }

        ws.on('close', () => {
            if (channel && connections.has(channel)) {
                connections.get(channel).delete(ws);
                if (connections.get(channel).size === 0) {
                    connections.delete(channel);
                }
                console.log(`❌ Client disconnected from channel: ${channel}`);
            }
        });

        ws.on('error', (error) => {
            console.error('WebSocket error:', error);
        });

    } catch (error) {
        console.error('Connection error:', error);
    }
});

// HTTP endpoints для публикации сообщений (Bitrix API)
app.post('/bitrix/sub/', async (req, res) => {
    try {
      console.log(req.body);
        const { channel, message, signature } = req.body;

        console.log('📨 Received push request:', { channel, signature });

        // Проверка подписи
        if (signature !== SIGNATURE_KEY) {
            console.warn('❌ Invalid signature');
            return res.status(403).json({ error: 'Invalid signature' });
        }

        if (!channel || !message) {
            return res.status(400).json({ error: 'Channel and message are required' });
        }

        const messageString = JSON.stringify(message);

        // Сохраняем в Redis
        await redisClient.publish(channel, messageString);

        // Отправляем через WebSocket
        if (connections.has(channel)) {
            const channelConnections = connections.get(channel);
            console.log(`📤 Sending to ${channelConnections.size} clients on channel: ${channel}`);

            channelConnections.forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    try {
                        client.send(messageString);
                    } catch (error) {
                        console.error('Error sending message to client:', error);
                    }
                }
            });
        }

        console.log('✅ Message published successfully');
        res.json({
            status: 'ok',
            channel: channel,
            clients: connections.has(channel) ? connections.get(channel).size : 0
        });

    } catch (error) {
        console.error('❌ Publish error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// Статус сервера
app.get('/status', (req, res) => {
    res.json({
        status: 'ok',
        server: 'Bitrix24 Push Server',
        version: '1.0.0',
        connections: connections.size,
        channels: Array.from(connections.keys()),
        total_clients: Array.from(connections.values()).reduce((acc, set) => acc + set.size, 0)
    });
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'healthy', timestamp: new Date().toISOString() });
});

// Запуск сервера
server.listen(PUSH_PORT, () => {
    console.log('🚀 Bitrix24 Push Server started');
    console.log(`📡 HTTP Server running on port ${PUSH_PORT}`);
    console.log(`🔌 WebSocket Server running on path /bitrix/subws/`);
    console.log(`🔑 Signature key: ${SIGNATURE_KEY}`);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
    console.log('🛑 Shutting down server...');
    await redisClient.quit();
    server.close();
    process.exit(0);
});
