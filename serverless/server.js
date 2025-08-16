const express = require('express');
const cors = require('cors');
const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Simulacija baze podatkov (v produkciji bi bilo prava baza)
let logs = [
    {
        id: 1,
        action: 'professor_view',
        user_id: 1,
        details: 'Pogled na profesorje',
        timestamp: new Date().toISOString()
    },
    {
        id: 2,
        action: 'quiz_start',
        user_id: 1,
        details: 'Začetek kviza',
        timestamp: new Date().toISOString()
    }
];

let notifications = [
    {
        id: 1,
        user_id: 1,
        message: 'Dobrodošli v aplikacijo!',
        type: 'welcome',
        priority: 'normal',
        read: false,
        timestamp: new Date().toISOString()
    },
    {
        id: 2,
        user_id: 1,
        message: 'Novi komentarji so na voljo',
        type: 'update',
        priority: 'high',
        read: true,
        timestamp: new Date().toISOString()
    }
];

// Analytics API (Storitev A)
app.get('/api/analytics', (req, res) => {
    const total_logs = logs.length;
    const today = new Date().toDateString();
    const today_logs = logs.filter(log => 
        new Date(log.timestamp).toDateString() === today
    ).length;
    
    const actionCounts = {};
    logs.forEach(log => {
        actionCounts[log.action] = (actionCounts[log.action] || 0) + 1;
    });
    const popular_actions = Object.keys(actionCounts)
        .sort((a, b) => actionCounts[b] - actionCounts[a])
        .slice(0, 3);
    
    res.json({ 
        message: 'Statistike pridobljene', 
        data: { 
            total_logs, 
            today_logs, 
            popular_actions,
            last_updated: new Date().toISOString()
        } 
    });
});

app.post('/api/analytics', (req, res) => {
    const { action, user_id, details } = req.body;
    
    if (!action || !user_id) {
        return res.status(400).json({ error: 'Manjkajo obvezni podatki' });
    }
    
    const newLog = {
        id: Date.now(),
        action,
        user_id,
        details: details || '',
        timestamp: new Date().toISOString()
    };
    
    logs.push(newLog);
    
    res.status(201).json({ 
        message: 'Log dodan', 
        data: newLog
    });
});

app.put('/api/analytics', (req, res) => {
    const { id, action, user_id, details } = req.body;
    
    const logIndex = logs.findIndex(log => log.id == id);
    if (logIndex === -1) {
        return res.status(404).json({ error: 'Log ni najden' });
    }
    
    logs[logIndex] = {
        ...logs[logIndex],
        action: action || logs[logIndex].action,
        user_id: user_id || logs[logIndex].user_id,
        details: details || logs[logIndex].details,
        updated_at: new Date().toISOString()
    };
    
    res.json({ 
        message: 'Log posodobljen', 
        data: logs[logIndex]
    });
});

app.delete('/api/analytics', (req, res) => {
    const { id } = req.query;
    
    const logIndex = logs.findIndex(log => log.id == id);
    if (logIndex === -1) {
        return res.status(404).json({ error: 'Log ni najden' });
    }
    
    const deletedLog = logs.splice(logIndex, 1)[0];
    
    res.json({ 
        message: 'Log izbrisan', 
        data: { 
            id: deletedLog.id, 
            deleted_at: new Date().toISOString() 
        } 
    });
});

// Notifications API (Storitev B)
app.get('/api/notifications', (req, res) => {
    const { user_id: queryUserId, unread_only } = req.query;
    let filteredNotifications = [...notifications];
    
    if (queryUserId) {
        filteredNotifications = filteredNotifications.filter(n => n.user_id == queryUserId);
    }
    
    if (unread_only === 'true') {
        filteredNotifications = filteredNotifications.filter(n => !n.read);
    }
    
    res.json({ 
        message: 'Notifikacije pridobljene', 
        data: filteredNotifications,
        count: filteredNotifications.length
    });
});

app.post('/api/notifications', (req, res) => {
    const { user_id, message, type, priority } = req.body;
    
    if (!user_id || !message) {
        return res.status(400).json({ error: 'Manjkajo obvezni podatki' });
    }
    
    const newNotification = {
        id: Date.now(),
        user_id,
        message,
        type: type || 'info',
        priority: priority || 'normal',
        read: false,
        timestamp: new Date().toISOString()
    };
    
    notifications.push(newNotification);
    
    res.status(201).json({ 
        message: 'Notifikacija dodana', 
        data: newNotification
    });
});

app.put('/api/notifications', (req, res) => {
    const { id, read, message, type, priority } = req.body;
    
    const notificationIndex = notifications.findIndex(n => n.id == id);
    if (notificationIndex === -1) {
        return res.status(404).json({ error: 'Notifikacija ni najdena' });
    }
    
    notifications[notificationIndex] = {
        ...notifications[notificationIndex],
        read: read !== undefined ? read : notifications[notificationIndex].read,
        message: message || notifications[notificationIndex].message,
        type: type || notifications[notificationIndex].type,
        priority: priority || notifications[notificationIndex].priority,
        updated_at: new Date().toISOString()
    };
    
    res.json({ 
        message: 'Notifikacija posodobljena', 
        data: notifications[notificationIndex]
    });
});

app.delete('/api/notifications', (req, res) => {
    const { id } = req.query;
    
    const notificationIndex = notifications.findIndex(n => n.id == id);
    if (notificationIndex === -1) {
        return res.status(404).json({ error: 'Notifikacija ni najdena' });
    }
    
    const deletedNotification = notifications.splice(notificationIndex, 1)[0];
    
    res.json({ 
        message: 'Notifikacija izbrisana', 
        data: { 
            id: deletedNotification.id, 
            deleted_at: new Date().toISOString() 
        } 
    });
});

// Health check
app.get('/health', (req, res) => {
    res.json({ 
        status: 'OK', 
        timestamp: new Date().toISOString(),
        services: ['analytics', 'notifications'],
        data: {
            logs_count: logs.length,
            notifications_count: notifications.length
        }
    });
});

// Reset data (za testiranje)
app.post('/api/reset', (req, res) => {
    logs = [];
    notifications = [];
    res.json({ message: 'Podatki ponastavljeni' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Serverless strežnik teče na portu ${PORT}`);
    console.log(`📊 Analytics API: http://localhost:${PORT}/api/analytics`);
    console.log(`🔔 Notifications API: http://localhost:${PORT}/api/notifications`);
    console.log(`❤️  Health check: http://localhost:${PORT}/health`);
    console.log(`🔄 Reset data: POST http://localhost:${PORT}/api/reset`);
});
